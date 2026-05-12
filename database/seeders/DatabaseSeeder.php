<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesHeader;
use App\Models\SalesLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTestUser();
        $customers = $this->seedCustomers();
        $items = $this->seedItems();
        $this->seedSalesInvoices($customers, $items);
    }

    protected function seedTestUser(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }

    /** @return array<int, Customer> */
    protected function seedCustomers(): array
    {
        $rows = [
            ['customer_no' => 'C-0001', 'name' => 'Acme Corporation', 'email' => 'billing@acme.test',  'phone' => '+254700000001', 'address' => 'P.O. Box 1, Nairobi'],
            ['customer_no' => 'C-0002', 'name' => 'Globex Ltd',       'email' => 'ap@globex.test',     'phone' => '+254700000002', 'address' => 'P.O. Box 2, Nairobi'],
            ['customer_no' => 'C-0003', 'name' => 'Initech',          'email' => 'finance@initech.test','phone' => '+254700000003', 'address' => 'P.O. Box 3, Nairobi'],
            ['customer_no' => 'C-0004', 'name' => 'Soylent Foods',    'email' => null,                  'phone' => null,            'address' => null],
        ];

        $customers = [];
        foreach ($rows as $row) {
            $customers[] = Customer::updateOrCreate(['customer_no' => $row['customer_no']], $row);
        }

        return $customers;
    }

    /** @return array<int, Item> */
    protected function seedItems(): array
    {
        $rows = [
            ['item_no' => 'I-0001', 'name' => 'A4 Printer Paper',  'description' => 'Ream of 500 sheets, 80gsm',  'unit_price' => 750.00,  'unit_of_measure' => 'REAM'],
            ['item_no' => 'I-0002', 'name' => 'Ballpoint Pen',     'description' => 'Blue ink, box of 50',         'unit_price' => 1200.00, 'unit_of_measure' => 'BOX'],
            ['item_no' => 'I-0003', 'name' => 'Stapler',           'description' => 'Heavy duty desktop stapler',  'unit_price' => 1800.00, 'unit_of_measure' => 'PCS'],
            ['item_no' => 'I-0004', 'name' => 'Office Chair',      'description' => 'Ergonomic mesh-back chair',   'unit_price' => 18500.00,'unit_of_measure' => 'PCS'],
            ['item_no' => 'I-0005', 'name' => 'Laser Toner',       'description' => 'Black, compatible HP 85A',    'unit_price' => 6200.00, 'unit_of_measure' => 'PCS'],
        ];

        $items = [];
        foreach ($rows as $row) {
            $items[] = Item::updateOrCreate(['item_no' => $row['item_no']], $row);
        }

        return $items;
    }

    /**
     * @param  array<int, Customer>  $customers
     * @param  array<int, Item>      $items
     */
    protected function seedSalesInvoices(array $customers, array $items): void
    {
        $invoices = [
            [
                'invoice_no' => 'SI-0001',
                'customer'   => $customers[0],
                'invoice_date' => Carbon::today()->subDays(3),
                'due_date'     => Carbon::today()->addDays(27),
                'status'       => SalesHeader::STATUS_DRAFT,
                'lines' => [
                    ['item' => $items[0], 'quantity' => 10, 'tax' => 1200,  'discount' => 0],
                    ['item' => $items[1], 'quantity' => 2,  'tax' => 384,   'discount' => 0],
                    ['item' => $items[4], 'quantity' => 1,  'tax' => 992,   'discount' => 200],
                ],
            ],
            [
                'invoice_no' => 'SI-0002',
                'customer'   => $customers[1],
                'invoice_date' => Carbon::today()->subDay(),
                'due_date'     => Carbon::today()->addDays(29),
                'status'       => SalesHeader::STATUS_RELEASED,
                'lines' => [
                    ['item' => $items[3], 'quantity' => 5, 'tax' => 14800, 'discount' => 5000],
                    ['item' => $items[2], 'quantity' => 3, 'tax' => 864,   'discount' => 0],
                ],
            ],
            [
                'invoice_no' => 'SI-0003',
                'customer'   => $customers[2],
                'invoice_date' => Carbon::today(),
                'due_date'     => Carbon::today()->addDays(14),
                'status'       => SalesHeader::STATUS_DRAFT,
                'lines' => [
                    ['item' => $items[0], 'quantity' => 25, 'tax' => 3000, 'discount' => 0],
                ],
            ],
        ];

        foreach ($invoices as $payload) {
            /** @var Customer $customer */
            $customer = $payload['customer'];

            $header = SalesHeader::updateOrCreate(
                ['invoice_no' => $payload['invoice_no']],
                [
                    'customer_id' => $customer->id,
                    'invoice_date' => $payload['invoice_date'],
                    'due_date' => $payload['due_date'],
                    'status' => $payload['status'],
                ],
            );

            $header->lines()->delete();

            foreach ($payload['lines'] as $i => $line) {
                /** @var Item $item */
                $item = $line['item'];

                SalesLine::create([
                    'sales_header_id' => $header->id,
                    'line_no' => ($i + 1) * 10,
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $line['discount'],
                    'tax_amount' => $line['tax'],
                ]);
            }

            $header->recalculateTotals();
        }
    }
}
