<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_index_page_loads(): void
    {
        $this->get(route('customers.index'))->assertOk();
    }

    public function test_can_create_a_customer(): void
    {
        Livewire::test('pages::customers.edit')
            ->set('customer_no', 'C-NEW')
            ->set('name', 'New Co')
            ->set('email', 'hello@new.test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['customer_no' => 'C-NEW', 'name' => 'New Co']);
    }

    public function test_can_edit_an_existing_customer(): void
    {
        $customer = Customer::create(['customer_no' => 'C-1', 'name' => 'Old name']);

        Livewire::test('pages::customers.edit', ['customer' => $customer->id])
            ->assertSet('name', 'Old name')
            ->set('name', 'New name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New name', $customer->fresh()->name);
    }

    public function test_customer_no_must_be_unique(): void
    {
        Customer::create(['customer_no' => 'C-DUP', 'name' => 'Existing']);

        Livewire::test('pages::customers.edit')
            ->set('customer_no', 'C-DUP')
            ->set('name', 'Another')
            ->call('save')
            ->assertHasErrors(['customer_no']);
    }
}
