<?php

use App\Models\Item;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Item')] class extends Component {
    public ?int $itemId = null;
    public string $item_no = '';
    public string $name = '';
    public ?string $description = null;
    public float $unit_price = 0;
    public string $unit_of_measure = 'PCS';

    public function mount(?int $item = null): void
    {
        if ($item) {
            $record = Item::findOrFail($item);
            $this->itemId = $record->id;
            $this->item_no = $record->item_no;
            $this->name = $record->name;
            $this->description = $record->description;
            $this->unit_price = (float) $record->unit_price;
            $this->unit_of_measure = $record->unit_of_measure;
        } else {
            $this->item_no = $this->nextItemNo();
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'item_no' => ['required', 'string', 'max:50', Rule::unique('items', 'item_no')->ignore($this->itemId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
        ]);

        if ($this->itemId) {
            Item::findOrFail($this->itemId)->update($validated);
            Flux::toast(variant: 'success', text: __('Item updated.'));
        } else {
            Item::create($validated);
            Flux::toast(variant: 'success', text: __('Item created.'));
        }

        $this->redirectRoute('items.index', navigate: true);
    }

    protected function nextItemNo(): string
    {
        $last = Item::query()->orderByDesc('id')->value('item_no');
        $n = (int) preg_replace('/\D/', '', (string) $last) + 1;
        return 'I-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}; ?>

<div class="max-w-3xl flex flex-col gap-4">
        <flux:heading size="xl">
            {{ $itemId ? __('Edit item') : __('New item') }}
        </flux:heading>

        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:input wire:model="item_no" :label="__('Item No.')" required />
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="unit_price" :label="__('Unit price')" type="number" step="0.01" min="0" required />
                <flux:input wire:model="unit_of_measure" :label="__('Unit of measure')" required />
            </div>

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                <flux:button :href="route('items.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
</div>
