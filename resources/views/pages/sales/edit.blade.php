<?php

use App\Actions\Sales\PostSalesInvoice;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesHeader;
use App\Models\SalesLine;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sales invoice')] class extends Component {
    public ?int $headerId = null;
    public string $invoice_no = '';
    public ?int $customer_id = null;
    public string $invoice_date = '';
    public ?string $due_date = null;
    public string $status = SalesHeader::STATUS_DRAFT;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public function mount(?int $sale = null): void
    {
        if ($sale) {
            $header = SalesHeader::with('lines')->findOrFail($sale);
            $this->headerId = $header->id;
            $this->invoice_no = $header->invoice_no;
            $this->customer_id = $header->customer_id;
            $this->invoice_date = $header->invoice_date?->format('Y-m-d') ?? '';
            $this->due_date = $header->due_date?->format('Y-m-d');
            $this->status = $header->status;
            $this->lines = $header->lines->map(fn (SalesLine $l) => [
                'id' => $l->id,
                'line_no' => $l->line_no,
                'item_id' => $l->item_id,
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount_amount' => (float) $l->discount_amount,
                'tax_amount' => (float) $l->tax_amount,
            ])->all();
        } else {
            $this->invoice_no = $this->nextInvoiceNo();
            $this->invoice_date = Carbon::today()->format('Y-m-d');
            $this->due_date = Carbon::today()->addDays(30)->format('Y-m-d');
            $this->addLine();
        }
    }

    public function addLine(): void
    {
        $nextLineNo = collect($this->lines)->max('line_no') ?? 0;

        $this->lines[] = [
            'id' => null,
            'line_no' => $nextLineNo + 10,
            'item_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function updatedLines(mixed $value, string $key): void
    {
        if (! str_ends_with($key, '.item_id')) {
            return;
        }

        [$idx] = explode('.', $key);
        $idx = (int) $idx;
        $itemId = $this->lines[$idx]['item_id'] ?? null;

        if (! $itemId) {
            return;
        }

        $item = Item::find($itemId);
        if (! $item) {
            return;
        }

        $this->lines[$idx]['description'] = $item->name;
        $this->lines[$idx]['unit_price'] = (float) $item->unit_price;
    }

    public function save(): SalesHeader
    {
        $validated = $this->validate([
            'invoice_no' => ['required', 'string', 'max:50', Rule::unique('sales_headers', 'invoice_no')->ignore($this->headerId)],
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'released', 'posted', 'cancelled'])],
            'lines' => ['array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $header = $this->headerId
            ? SalesHeader::findOrFail($this->headerId)
            : new SalesHeader();

        if ($header->status === SalesHeader::STATUS_POSTED) {
            Flux::toast(variant: 'danger', text: __('Posted invoices cannot be edited.'));
            return $header;
        }

        $header->fill([
            'invoice_no' => $validated['invoice_no'],
            'customer_id' => $validated['customer_id'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'],
        ])->save();

        $keepIds = [];
        foreach ($validated['lines'] as $i => $lineData) {
            $line = isset($this->lines[$i]['id']) && $this->lines[$i]['id']
                ? $header->lines()->whereKey($this->lines[$i]['id'])->firstOrNew()
                : new SalesLine();

            $line->fill([
                'sales_header_id' => $header->id,
                'line_no' => $this->lines[$i]['line_no'],
                'item_id' => $lineData['item_id'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit_price' => $lineData['unit_price'],
                'discount_amount' => $lineData['discount_amount'] ?? 0,
                'tax_amount' => $lineData['tax_amount'] ?? 0,
            ])->save();

            $keepIds[] = $line->id;
        }

        $header->lines()->whereNotIn('id', $keepIds)->delete();
        $header->recalculateTotals();

        $this->headerId = $header->id;
        Flux::toast(variant: 'success', text: __('Invoice saved.'));

        return $header->fresh(['lines']);
    }

    public function post(PostSalesInvoice $action): void
    {
        $header = $this->save();

        try {
            $posted = $action->handle($header);
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
            return;
        }

        Flux::toast(variant: 'success', text: __('Invoice posted as :no', ['no' => $posted->posted_invoice_no]));
        $this->redirectRoute('sales-posted.show', $posted->id, navigate: true);
    }

    protected function nextInvoiceNo(): string
    {
        $last = SalesHeader::query()->orderByDesc('id')->value('invoice_no');
        $n = (int) preg_replace('/\D/', '', (string) $last) + 1;
        return 'SI-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    #[Computed]
    public function customers()
    {
        return Customer::orderBy('name')->get(['id', 'customer_no', 'name']);
    }

    #[Computed]
    public function items()
    {
        return Item::orderBy('name')->get(['id', 'item_no', 'name', 'unit_price', 'unit_of_measure']);
    }

    #[Computed]
    public function totals(): array
    {
        $subtotal = 0;
        $tax = 0;
        $discount = 0;

        foreach ($this->lines as $l) {
            $subtotal += (float) ($l['quantity'] ?? 0) * (float) ($l['unit_price'] ?? 0);
            $tax += (float) ($l['tax_amount'] ?? 0);
            $discount += (float) ($l['discount_amount'] ?? 0);
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $subtotal + $tax - $discount,
        ];
    }

    #[Computed]
    public function isReadOnly(): bool
    {
        return $this->status === SalesHeader::STATUS_POSTED;
    }
}; ?>

<div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="xl">
                {{ $headerId ? __('Invoice :no', ['no' => $invoice_no]) : __('New sales invoice') }}
            </flux:heading>

            <div class="flex gap-2">
                <flux:button :href="route('sales.index')" wire:navigate variant="ghost">{{ __('Back') }}</flux:button>

                @unless ($this->isReadOnly)
                    <flux:button wire:click="save" variant="filled">{{ __('Save') }}</flux:button>
                    <flux:button wire:click="post" variant="primary"
                                 wire:confirm="{{ __('Post this invoice? Posting cannot be undone.') }}">
                        {{ __('Post invoice') }}
                    </flux:button>
                @endunless
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:input wire:model="invoice_no" :label="__('Invoice No.')" :readonly="$this->isReadOnly" required />

            <flux:select wire:model="customer_id" :label="__('Customer')" :disabled="$this->isReadOnly" required>
                <flux:select.option value="">{{ __('— select customer —') }}</flux:select.option>
                @foreach ($this->customers as $c)
                    <flux:select.option value="{{ $c->id }}">{{ $c->customer_no }} — {{ $c->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model="invoice_date" :label="__('Invoice date')" :readonly="$this->isReadOnly" required />
            <flux:input type="date" wire:model="due_date" :label="__('Due date')" :readonly="$this->isReadOnly" />

            <flux:select wire:model="status" :label="__('Status')" :disabled="$this->isReadOnly">
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="released">{{ __('Released') }}</flux:select.option>
                <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
                @if ($this->isReadOnly)
                    <flux:select.option value="posted">{{ __('Posted') }}</flux:select.option>
                @endif
            </flux:select>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between p-4">
                <flux:heading size="lg">{{ __('Lines') }}</flux:heading>
                @unless ($this->isReadOnly)
                    <flux:button size="sm" icon="plus" wire:click="addLine">{{ __('Add line') }}</flux:button>
                @endunless
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-16">{{ __('#') }}</flux:table.column>
                    <flux:table.column>{{ __('Item') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column class="w-24">{{ __('Qty') }}</flux:table.column>
                    <flux:table.column class="w-32">{{ __('Unit price') }}</flux:table.column>
                    <flux:table.column class="w-28">{{ __('Discount') }}</flux:table.column>
                    <flux:table.column class="w-28">{{ __('Tax') }}</flux:table.column>
                    <flux:table.column class="w-32 text-right">{{ __('Line amount') }}</flux:table.column>
                    @unless ($this->isReadOnly)
                        <flux:table.column class="w-16"></flux:table.column>
                    @endunless
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($lines as $i => $line)
                        @php
                            $qty = (float) ($line['quantity'] ?? 0);
                            $price = (float) ($line['unit_price'] ?? 0);
                            $lineAmount = ($qty * $price) + (float) ($line['tax_amount'] ?? 0) - (float) ($line['discount_amount'] ?? 0);
                        @endphp
                        <flux:table.row :key="'line-'.$i">
                            <flux:table.cell>
                                <flux:input size="sm" wire:model="lines.{{ $i }}.line_no" type="number" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:select size="sm" wire:model.live="lines.{{ $i }}.item_id" :disabled="$this->isReadOnly">
                                    <flux:select.option value="">{{ __('— select —') }}</flux:select.option>
                                    @foreach ($this->items as $item)
                                        <flux:select.option value="{{ $item->id }}">
                                            {{ $item->item_no }} — {{ $item->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input size="sm" wire:model="lines.{{ $i }}.description" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input size="sm" wire:model.blur="lines.{{ $i }}.quantity" type="number" step="0.01" min="0" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input size="sm" wire:model.blur="lines.{{ $i }}.unit_price" type="number" step="0.01" min="0" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input size="sm" wire:model.blur="lines.{{ $i }}.discount_amount" type="number" step="0.01" min="0" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:input size="sm" wire:model.blur="lines.{{ $i }}.tax_amount" type="number" step="0.01" min="0" :readonly="$this->isReadOnly" />
                            </flux:table.cell>
                            <flux:table.cell class="text-right font-mono">
                                {{ number_format($lineAmount, 2) }}
                            </flux:table.cell>
                            @unless ($this->isReadOnly)
                                <flux:table.cell>
                                    <flux:button size="xs" variant="danger" icon="trash" wire:click="removeLine({{ $i }})" />
                                </flux:table.cell>
                            @endunless
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9" class="text-center text-zinc-500">
                                {{ __('No lines yet. Click "Add line" to start.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="flex justify-end p-4">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm min-w-[18rem]">
                    <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                    <dd class="text-right font-mono">{{ number_format($this->totals['subtotal'], 2) }}</dd>
                    <dt class="text-zinc-500">{{ __('Tax') }}</dt>
                    <dd class="text-right font-mono">{{ number_format($this->totals['tax'], 2) }}</dd>
                    <dt class="text-zinc-500">{{ __('Discount') }}</dt>
                    <dd class="text-right font-mono">-{{ number_format($this->totals['discount'], 2) }}</dd>
                    <dt class="font-semibold">{{ __('Total') }}</dt>
                    <dd class="text-right font-mono font-semibold">{{ number_format($this->totals['total'], 2) }}</dd>
                </dl>
            </div>
        </div>
</div>
