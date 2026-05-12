<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_no', 'name', 'email', 'phone', 'address'])]
class Customer extends Model
{
    public function salesHeaders(): HasMany
    {
        return $this->hasMany(SalesHeader::class);
    }

    public function postedSalesHeaders(): HasMany
    {
        return $this->hasMany(PostedSalesHeader::class);
    }
}
