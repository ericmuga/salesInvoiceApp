<?php

use App\Models\PostedSalesHeader;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Posted invoice')] class extends Component {
    public int $postedId;

    public function mount(int $postedSale): void
    {
        $this->postedId = $postedSale;
    }

    #[Computed]
    public function header(): PostedSalesHeader
    {
        return PostedSalesHeader::with('lines.item')->findOrFail($this->postedId);
    }
}; ?>

@php($header = $this->header)

<div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="xl">{{ __('Posted invoice :no', ['no' => $header->posted_invoice_no]) }}</flux:heading>
            <flux:button :href="route('sales-posted.index')" wire:navigate variant="ghost">{{ __('Back') }}</flux:button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <div>
                <flux:text class="text-zinc-500">{{ __('Source invoice') }}</flux:text>
                <flux:text class="font-mono">{{ $header->source_invoice_no }}</flux:text>
            </div>
            <div>
                <flux:text class="text-zinc-500">{{ __('Customer') }}</flux:text>
                <flux:text>{{ $header->customer_no }} — {{ $header->customer_name }}</flux:text>
            </div>
            <div>
                <flux:text class="text-zinc-500">{{ __('Invoice date') }}</flux:text>
                <flux:text>{{ $header->invoice_date?->format('Y-m-d') }}</flux:text>
            </div>
            <div>
                <flux:text class="text-zinc-500">{{ __('Posting date') }}</flux:text>
                <flux:text>{{ $header->posting_date?->format('Y-m-d') }}</flux:text>
            </div>
            <div>
                <flux:text class="text-zinc-500">{{ __('Due date') }}</flux:text>
                <flux:text>{{ $header->due_date?->format('Y-m-d') ?? '—' }}</flux:text>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Item No.') }}</flux:table.column>
                    <flux:table.column>{{ __('Description') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Qty') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Unit price') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Discount') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Tax') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Line amount') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($header->lines as $line)
                        <flux:table.row :key="$line->id">
                            <flux:table.cell class="font-mono">{{ $line->item_no }}</flux:table.cell>
                            <flux:table.cell>{{ $line->description }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">{{ number_format((float) $line->quantity, 2) }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">{{ number_format((float) $line->unit_price, 2) }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">{{ number_format((float) $line->discount_amount, 2) }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">{{ number_format((float) $line->tax_amount, 2) }}</flux:table.cell>
                            <flux:table.cell class="text-right font-mono">{{ number_format((float) $line->line_amount, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="flex justify-end p-4">
                <dl class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm min-w-[18rem]">
                    <dt class="text-zinc-500">{{ __('Subtotal') }}</dt>
                    <dd class="text-right font-mono">{{ number_format((float) $header->subtotal, 2) }}</dd>
                    <dt class="text-zinc-500">{{ __('Tax') }}</dt>
                    <dd class="text-right font-mono">{{ number_format((float) $header->tax_amount, 2) }}</dd>
                    <dt class="text-zinc-500">{{ __('Discount') }}</dt>
                    <dd class="text-right font-mono">-{{ number_format((float) $header->discount_amount, 2) }}</dd>
                    <dt class="font-semibold">{{ __('Total') }}</dt>
                    <dd class="text-right font-mono font-semibold">{{ number_format((float) $header->total_amount, 2) }}</dd>
                </dl>
            </div>
        </div>
</div>
