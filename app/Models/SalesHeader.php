<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_no',
    'customer_id',
    'invoice_date',
    'due_date',
    'status',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total_amount',
])]
class SalesHeader extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RELEASED = 'released';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
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
        return $this->hasMany(SalesLine::class)->orderBy('line_no');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RELEASED], true);
    }

    public function recalculateTotals(): void
    {
        $lines = $this->lines()->get();

        $this->subtotal = $lines->sum(fn ($l) => (float) $l->quantity * (float) $l->unit_price);
        $this->tax_amount = $lines->sum('tax_amount');
        $this->discount_amount = $lines->sum('discount_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }
}
