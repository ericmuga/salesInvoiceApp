<?php

use App\Models\PostedSalesHeader;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Posted invoices')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoices()
    {
        return PostedSalesHeader::query()
            ->when($this->search, fn ($q) => $q
                ->where('posted_invoice_no', 'like', "%{$this->search}%")
                ->orWhere('source_invoice_no', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%"))
            ->orderByDesc('id')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-4">
        <flux:heading size="xl">{{ __('Posted invoices') }}</flux:heading>

        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by invoice no or customer...') }}" />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Posted No.') }}</flux:table.column>
                <flux:table.column>{{ __('Source No.') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice date') }}</flux:table.column>
                <flux:table.column>{{ __('Posting date') }}</flux:table.column>
                <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell class="font-mono">{{ $invoice->posted_invoice_no }}</flux:table.cell>
                        <flux:table.cell class="font-mono">{{ $invoice->source_invoice_no }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->customer_name }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->invoice_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->posting_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell class="text-right">{{ number_format((float) $invoice->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end">
                                <flux:button size="xs" :href="route('sales-posted.show', $invoice)" wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                            {{ __('No posted invoices yet.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->invoices->links() }}
</div>
