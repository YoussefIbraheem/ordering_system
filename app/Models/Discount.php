<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable('name', 'value', 'type')]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    #[Override]
    protected function casts()
    {
        return [
            'value' => 'decimal:2',
            'type' => DiscountType::class,
        ];
    }

    public function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => $value * 100
        );
    }
}
