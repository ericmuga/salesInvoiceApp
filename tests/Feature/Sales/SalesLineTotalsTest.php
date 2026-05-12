<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesHeader;
use App\Models\SalesLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesLineTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_amount_is_computed_on_save(): void
    {
        $header = $this->makeHeader();
        $item = $this->makeItem(['unit_price' => 100]);

        $line = SalesLine::create([
            'sales_header_id' => $header->id,
            'line_no' => 10,
            'item_id' => $item->id,
            'description' => $item->name,
            'quantity' => 3,
            'unit_price' => 100,
            'discount_amount' => 50,
            'tax_amount' => 48,
        ]);

        // 3 * 100 + 48 - 50 = 298
        $this->assertEquals(298, (float) $line->fresh()->line_amount);
    }

    public function test_recalculate_totals_sums_subtotal_tax_and_discount(): void
    {
        $header = $this->makeHeader();
        $itemA = $this->makeItem(['unit_price' => 100]);
        $itemB = $this->makeItem(['unit_price' => 200]);

        SalesLine::create(['sales_header_id' => $header->id, 'line_no' => 10, 'item_id' => $itemA->id, 'description' => 'A', 'quantity' => 2, 'unit_price' => 100, 'discount_amount' => 0,  'tax_amount' => 32]);
        SalesLine::create(['sales_header_id' => $header->id, 'line_no' => 20, 'item_id' => $itemB->id, 'description' => 'B', 'quantity' => 5, 'unit_price' => 200, 'discount_amount' => 100, 'tax_amount' => 160]);

        $header->recalculateTotals();

        $fresh = $header->fresh();
        $this->assertEquals(1200, (float) $fresh->subtotal, 'subtotal should be 2*100 + 5*200 = 1200');
        $this->assertEquals(192, (float) $fresh->tax_amount);
        $this->assertEquals(100, (float) $fresh->discount_amount);
        $this->assertEquals(1292, (float) $fresh->total_amount, 'total = 1200 + 192 - 100');
    }

    protected function makeHeader(array $overrides = []): SalesHeader
    {
        $customer = Customer::create([
            'customer_no' => 'C-T'.now()->timestamp.random_int(100, 999),
            'name' => 'Test Customer',
        ]);

        return SalesHeader::create(array_merge([
            'invoice_no' => 'SI-T'.now()->timestamp.random_int(100, 999),
            'customer_id' => $customer->id,
            'invoice_date' => today(),
            'status' => SalesHeader::STATUS_DRAFT,
        ], $overrides));
    }

    protected function makeItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_no' => 'I-T'.now()->timestamp.random_int(100, 999),
            'name' => 'Test Item',
            'unit_price' => 100,
            'unit_of_measure' => 'PCS',
        ], $overrides));
    }
}
