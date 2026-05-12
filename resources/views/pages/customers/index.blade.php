<?php

use App\Models\Customer;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $customer = Customer::findOrFail($id);

        if ($customer->salesHeaders()->exists() || $customer->postedSalesHeaders()->exists()) {
            Flux::toast(variant: 'danger', text: __('Cannot delete a customer that has invoices.'));
            return;
        }

        $customer->delete();
        Flux::toast(variant: 'success', text: __('Customer deleted.'));
    }

    #[Computed]
    public function customers()
    {
        return Customer::query()
            ->when($this->search, fn ($q) => $q
                ->where('customer_no', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderBy('customer_no')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="xl">{{ __('Customers') }}</flux:heading>

            <flux:button :href="route('customers.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('New customer') }}
            </flux:button>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by number, name or email...') }}" />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('No.') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Phone') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell class="font-mono">{{ $customer->customer_no }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->phone ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" :href="route('customers.edit', $customer)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="xs" variant="danger" wire:click="delete({{ $customer->id }})"
                                             wire:confirm="{{ __('Delete this customer?') }}">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">
                            {{ __('No customers found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->customers->links() }}
</div>
