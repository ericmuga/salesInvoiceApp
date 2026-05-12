<?php

namespace App\Actions\Sales;

use App\Models\PostedSalesHeader;
use App\Models\SalesHeader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostSalesInvoice
{
    public function handle(SalesHeader $header): PostedSalesHeader
    {
        if ($header->status === SalesHeader::STATUS_POSTED) {
            throw new RuntimeException("Invoice {$header->invoice_no} is already posted.");
        }

        if ($header->status === SalesHeader::STATUS_CANCELLED) {
            throw new RuntimeException("Cannot post a cancelled invoice.");
        }

        $header->loadMissing(['customer', 'lines.item']);

        if ($header->lines->isEmpty()) {
            throw new RuntimeException("Cannot post an invoice with no lines.");
        }

        return DB::transaction(function () use ($header) {
            $header->recalculateTotals();

            $posted = PostedSalesHeader::create([
                'posted_invoice_no' => $this->generatePostedNo(),
                'source_invoice_no' => $header->invoice_no,
                'customer_id' => $header->customer_id,
                'customer_no' => $header->customer->customer_no,
                'customer_name' => $header->customer->name,
                'invoice_date' => $header->invoice_date,
                'posting_date' => Carbon::today(),
                'due_date' => $header->due_date,
                'subtotal' => $header->subtotal,
                'tax_amount' => $header->tax_amount,
                'discount_amount' => $header->discount_amount,
                'total_amount' => $header->total_amount,
            ]);

            foreach ($header->lines as $line) {
                $posted->lines()->create([
                    'item_id' => $line->item_id,
                    'item_no' => $line->item?->item_no ?? '',
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                    'line_amount' => $line->line_amount,
                ]);
            }

            $header->update(['status' => SalesHeader::STATUS_POSTED]);

            return $posted;
        });
    }

    protected function generatePostedNo(): string
    {
        $prefix = 'PSI-'.Carbon::today()->format('Ymd').'-';

        do {
            $candidate = $prefix.Str::upper(Str::random(5));
        } while (PostedSalesHeader::where('posted_invoice_no', $candidate)->exists());

        return $candidate;
    }
}
