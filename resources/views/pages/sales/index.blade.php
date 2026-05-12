<?php

use App\Models\SalesHeader;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sales invoices')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $header = SalesHeader::findOrFail($id);

        if ($header->status === SalesHeader::STATUS_POSTED) {
            Flux::toast(variant: 'danger', text: __('Cannot delete a posted invoice.'));
            return;
        }

        $header->delete();
        Flux::toast(variant: 'success', text: __('Invoice deleted.'));
    }

    #[Computed]
    public function invoices()
    {
        return SalesHeader::query()
            ->with('customer')
            ->when($this->search, fn ($q) => $q->where('invoice_no', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="xl">{{ __('Sales invoices') }}</flux:heading>

            <flux:button :href="route('sales.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('New invoice') }}
            </flux:button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by invoice no...') }}" />
            <flux:select wire:model.live="status">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="released">{{ __('Released') }}</flux:select.option>
                <flux:select.option value="posted">{{ __('Posted') }}</flux:select.option>
                <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Invoice No.') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell class="font-mono">{{ $invoice->invoice_no }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->customer?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->invoice_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            @php
                                $variant = match ($invoice->status) {
                                    'draft' => 'zinc',
                                    'released' => 'blue',
                                    'posted' => 'lime',
                                    'cancelled' => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge :color="$variant" size="sm">{{ ucfirst($invoice->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-right">{{ number_format((float) $invoice->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" :href="route('sales.edit', $invoice)" wire:navigate>
                                    {{ $invoice->status === 'posted' ? __('View') : __('Edit') }}
                                </flux:button>
                                @if ($invoice->status !== 'posted')
                                    <flux:button size="xs" variant="danger" wire:click="delete({{ $invoice->id }})"
                                                 wire:confirm="{{ __('Delete this invoice?') }}">
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500">
                            {{ __('No invoices found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->invoices->links() }}
</div>
