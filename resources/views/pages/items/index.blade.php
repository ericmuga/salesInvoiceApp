<?php

use App\Models\Item;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Items')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $item = Item::findOrFail($id);

        if ($item->salesLines()->exists() || $item->postedSalesLines()->exists()) {
            Flux::toast(variant: 'danger', text: __('Cannot delete an item used on invoices.'));
            return;
        }

        $item->delete();
        Flux::toast(variant: 'success', text: __('Item deleted.'));
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->when($this->search, fn ($q) => $q
                ->where('item_no', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->orderBy('item_no')
            ->paginate(15);
    }
}; ?>

<div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="xl">{{ __('Items') }}</flux:heading>

            <flux:button :href="route('items.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('New item') }}
            </flux:button>
        </div>

        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search by number or name...') }}" />

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('No.') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('UoM') }}</flux:table.column>
                <flux:table.column class="text-right">{{ __('Unit price') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->items as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell class="font-mono">{{ $item->item_no }}</flux:table.cell>
                        <flux:table.cell>{{ $item->name }}</flux:table.cell>
                        <flux:table.cell>{{ $item->unit_of_measure }}</flux:table.cell>
                        <flux:table.cell class="text-right">{{ number_format((float) $item->unit_price, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" :href="route('items.edit', $item)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button size="xs" variant="danger" wire:click="delete({{ $item->id }})"
                                             wire:confirm="{{ __('Delete this item?') }}">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">
                            {{ __('No items found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        {{ $this->items->links() }}
</div>
