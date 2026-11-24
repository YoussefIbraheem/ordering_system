<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable("total", "subtotal", "discount_amount", "discount_type", "status", "user_id")]
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected function casts()
    {
        return [
            "total" => "decimal",
            "subtotal" => "decimal",
            "discount_amount" => "decimal",
            "status" => OrderStatus::class,
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
