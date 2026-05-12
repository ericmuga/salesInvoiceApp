<?php

use App\Models\Customer;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customer')] class extends Component {
    public ?int $customerId = null;
    public string $customer_no = '';
    public string $name = '';
    public ?string $email = null;
    public ?string $phone = null;
    public ?string $address = null;

    public function mount(?int $customer = null): void
    {
        if ($customer) {
            $record = Customer::findOrFail($customer);
            $this->customerId = $record->id;
            $this->customer_no = $record->customer_no;
            $this->name = $record->name;
            $this->email = $record->email;
            $this->phone = $record->phone;
            $this->address = $record->address;
        } else {
            $this->customer_no = $this->nextCustomerNo();
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'customer_no' => ['required', 'string', 'max:50', Rule::unique('customers', 'customer_no')->ignore($this->customerId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        if ($this->customerId) {
            Customer::findOrFail($this->customerId)->update($validated);
            Flux::toast(variant: 'success', text: __('Customer updated.'));
        } else {
            Customer::create($validated);
            Flux::toast(variant: 'success', text: __('Customer created.'));
        }

        $this->redirectRoute('customers.index', navigate: true);
    }

    protected function nextCustomerNo(): string
    {
        $last = Customer::query()->orderByDesc('id')->value('customer_no');
        $n = (int) preg_replace('/\D/', '', (string) $last) + 1;
        return 'C-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}; ?>

<div class="max-w-3xl flex flex-col gap-4">
        <flux:heading size="xl">
            {{ $customerId ? __('Edit customer') : __('New customer') }}
        </flux:heading>

        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:input wire:model="customer_no" :label="__('Customer No.')" required />
            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="email" :label="__('Email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:textarea wire:model="address" :label="__('Address')" rows="3" />

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                <flux:button :href="route('customers.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </form>
</div>
