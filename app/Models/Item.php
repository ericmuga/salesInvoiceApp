<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['item_no', 'name', 'description', 'unit_price', 'unit_of_measure'])]
class Item extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    public function salesLines(): HasMany
    {
        return $this->hasMany(SalesLine::class);
    }

    public function postedSalesLines(): HasMany
    {
        return $this->hasMany(PostedSalesLine::class);
    }
}
