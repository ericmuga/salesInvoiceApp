<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'posted_invoice_no',
    'source_invoice_no',
    'customer_id',
    'customer_no',
    'customer_name',
    'invoice_date',
    'posting_date',
    'due_date',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total_amount',
])]
class PostedSalesHeader extends Model
{
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'posting_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PostedSalesLine::class);
    }
}
