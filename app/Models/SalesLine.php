<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_header_id',
    'line_no',
    'item_id',
    'description',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'line_amount',
])]
class SalesLine extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesLine $line) {
            $gross = (float) $line->quantity * (float) $line->unit_price;
            $line->line_amount = $gross + (float) $line->tax_amount - (float) $line->discount_amount;
        });
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(SalesHeader::class, 'sales_header_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
