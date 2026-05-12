<?php

namespace Tests\Feature\Sales;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_index_page_loads(): void
    {
        $this->get(route('items.index'))->assertOk();
    }

    public function test_can_create_an_item(): void
    {
        Livewire::test('pages::items.edit')
            ->set('item_no', 'I-NEW')
            ->set('name', 'Widget')
            ->set('unit_price', 25.50)
            ->set('unit_of_measure', 'PCS')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('items.index'));

        $this->assertDatabaseHas('items', ['item_no' => 'I-NEW', 'name' => 'Widget']);
    }

    public function test_can_edit_an_item(): void
    {
        $item = Item::create(['item_no' => 'I-1', 'name' => 'Old', 'unit_price' => 10, 'unit_of_measure' => 'PCS']);

        Livewire::test('pages::items.edit', ['item' => $item->id])
            ->set('name', 'Renamed')
            ->set('unit_price', 99.99)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $item->fresh();
        $this->assertEquals('Renamed', $fresh->name);
        $this->assertEquals(99.99, (float) $fresh->unit_price);
    }
}
