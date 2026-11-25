<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public static function create(string $name, int $quantity_in_stock, float $price): Product
    {
        return Product::create([
            'name' => $name,
            'quantity_in_stock' => $quantity_in_stock,
            'price' => $price,
        ]);
    }

    public static function updateName(Product $product, string $name): Product
    {
        $product->update([
            'name' => $name,
        ]);

        return $product;
    }

    public static function updatePrice(Product $product, float $price): Product
    {
        $product->update([
            'price' => $price,
        ]);

        return $product;
    }

    public static function increaseStock(Product $product, int $quantity): bool
    {
        $product->quantity_in_stock += $quantity;
        $product->save();

        return true;
    }

    public static function decreaseStock(Product $product, int $quantity): bool
    {
        $product->quantity_in_stock -= $quantity;
        $product->save();

        return true;
    }
}
