<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\PostSalesInvoice;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PostedSalesHeader;
use App\Models\PostedSalesLine;
use App\Models\SalesHeader;
use App\Models\SalesLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PostSalesInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_posting_copies_header_and_lines_with_snapshot(): void
    {
        $header = $this->seedDraftInvoice();

        $posted = app(PostSalesInvoice::class)->handle($header);

        $this->assertInstanceOf(PostedSalesHeader::class, $posted);
        $this->assertEquals($header->invoice_no, $posted->source_invoice_no);
        $this->assertEquals($header->customer->customer_no, $posted->customer_no);
        $this->assertEquals($header->customer->name, $posted->customer_name);
        $this->assertEquals(today()->toDateString(), $posted->posting_date->toDateString());
        $this->assertEquals(2, $posted->lines()->count());

        $firstLine = $posted->lines()->first();
        $this->assertEquals('I-0001', $firstLine->item_no, 'item_no should be snapshotted');
    }

    public function test_posting_marks_original_as_posted(): void
    {
        $header = $this->seedDraftInvoice();

        app(PostSalesInvoice::class)->handle($header);

        $this->assertEquals(SalesHeader::STATUS_POSTED, $header->fresh()->status);
    }

    public function test_cannot_post_an_already_posted_invoice(): void
    {
        $header = $this->seedDraftInvoice();
        $header->update(['status' => SalesHeader::STATUS_POSTED]);

        $this->expectException(RuntimeException::class);
        app(PostSalesInvoice::class)->handle($header);
    }

    public function test_cannot_post_an_invoice_with_no_lines(): void
    {
        $customer = Customer::create(['customer_no' => 'C-EMPTY', 'name' => 'Empty Co']);
        $header = SalesHeader::create([
            'invoice_no' => 'SI-EMPTY',
            'customer_id' => $customer->id,
            'invoice_date' => today(),
            'status' => SalesHeader::STATUS_DRAFT,
        ]);

        $this->expectException(RuntimeException::class);
        app(PostSalesInvoice::class)->handle($header);
    }

    public function test_posting_is_atomic_and_does_not_create_duplicates_on_retry(): void
    {
        $header = $this->seedDraftInvoice();

        app(PostSalesInvoice::class)->handle($header);
        $firstCount = PostedSalesHeader::count();

        try {
            app(PostSalesInvoice::class)->handle($header->fresh());
        } catch (RuntimeException) {
            // expected
        }

        $this->assertEquals($firstCount, PostedSalesHeader::count(), 'Second post attempt should not create extra rows');
        $this->assertEquals(2, PostedSalesLine::count());
    }

    protected function seedDraftInvoice(): SalesHeader
    {
        $customer = Customer::create([
            'customer_no' => 'C-0001',
            'name' => 'Acme',
            'email' => 'ap@acme.test',
        ]);

        $itemA = Item::create(['item_no' => 'I-0001', 'name' => 'Item A', 'unit_price' => 100, 'unit_of_measure' => 'PCS']);
        $itemB = Item::create(['item_no' => 'I-0002', 'name' => 'Item B', 'unit_price' => 200, 'unit_of_measure' => 'PCS']);

        $header = SalesHeader::create([
            'invoice_no' => 'SI-1001',
            'customer_id' => $customer->id,
            'invoice_date' => today(),
            'due_date' => today()->addDays(30),
            'status' => SalesHeader::STATUS_DRAFT,
        ]);

        SalesLine::create(['sales_header_id' => $header->id, 'line_no' => 10, 'item_id' => $itemA->id, 'description' => 'Item A', 'quantity' => 2, 'unit_price' => 100, 'tax_amount' => 32, 'discount_amount' => 0]);
        SalesLine::create(['sales_header_id' => $header->id, 'line_no' => 20, 'item_id' => $itemB->id, 'description' => 'Item B', 'quantity' => 1, 'unit_price' => 200, 'tax_amount' => 32, 'discount_amount' => 50]);

        $header->recalculateTotals();

        return $header->fresh(['customer', 'lines.item']);
    }
}
