<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PostedSalesHeader;
use App\Models\SalesHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesInvoicePageTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->customer = Customer::create(['customer_no' => 'C-1', 'name' => 'Acme']);
        $this->item = Item::create(['item_no' => 'I-1', 'name' => 'Widget', 'unit_price' => 250, 'unit_of_measure' => 'PCS']);
    }

    public function test_create_page_loads_with_one_blank_line(): void
    {
        Livewire::test('pages::sales.edit')
            ->assertSet('status', SalesHeader::STATUS_DRAFT)
            ->assertCount('lines', 1);
    }

    public function test_selecting_item_autofills_price_and_description(): void
    {
        Livewire::test('pages::sales.edit')
            ->set('lines.0.item_id', $this->item->id)
            ->assertSet('lines.0.description', 'Widget')
            ->assertSet('lines.0.unit_price', 250.0);
    }

    public function test_can_save_a_draft_invoice(): void
    {
        Livewire::test('pages::sales.edit')
            ->set('customer_id', $this->customer->id)
            ->set('lines.0.item_id', $this->item->id)
            ->set('lines.0.description', 'Widget')
            ->set('lines.0.quantity', 4)
            ->set('lines.0.unit_price', 250)
            ->set('lines.0.tax_amount', 160)
            ->call('save')
            ->assertHasNoErrors();

        $invoice = SalesHeader::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1000, (float) $invoice->subtotal);
        $this->assertEquals(160, (float) $invoice->tax_amount);
        $this->assertEquals(1160, (float) $invoice->total_amount);
        $this->assertEquals(1, $invoice->lines()->count());
    }

    public function test_save_requires_at_least_one_valid_line(): void
    {
        Livewire::test('pages::sales.edit')
            ->set('customer_id', $this->customer->id)
            // leave default blank line with no item_id
            ->call('save')
            ->assertHasErrors(['lines.0.item_id']);
    }

    public function test_post_action_creates_posted_invoice_and_redirects(): void
    {
        $component = Livewire::test('pages::sales.edit')
            ->set('customer_id', $this->customer->id)
            ->set('lines.0.item_id', $this->item->id)
            ->set('lines.0.description', 'Widget')
            ->set('lines.0.quantity', 2)
            ->set('lines.0.unit_price', 250)
            ->call('post')
            ->assertHasNoErrors();

        $this->assertEquals(1, PostedSalesHeader::count());
        $posted = PostedSalesHeader::first();
        $this->assertEquals(SalesHeader::STATUS_POSTED, SalesHeader::first()->status);
        $component->assertRedirect(route('sales-posted.show', $posted->id));
    }

    public function test_posted_invoice_show_page_renders(): void
    {
        $invoice = SalesHeader::create([
            'invoice_no' => 'SI-X',
            'customer_id' => $this->customer->id,
            'invoice_date' => today(),
            'status' => SalesHeader::STATUS_DRAFT,
        ]);
        $invoice->lines()->create([
            'line_no' => 10,
            'item_id' => $this->item->id,
            'description' => 'Widget',
            'quantity' => 1,
            'unit_price' => 250,
        ]);
        $invoice->recalculateTotals();

        $posted = app(\App\Actions\Sales\PostSalesInvoice::class)->handle($invoice->fresh(['customer', 'lines.item']));

        $this->get(route('sales-posted.show', $posted->id))
            ->assertOk()
            ->assertSee($posted->posted_invoice_no)
            ->assertSee('Widget');
    }
}
