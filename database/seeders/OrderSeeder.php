<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i <= 5; $i++) {
            $products = Product::factory(5)->create();
            $user = User::factory()->create();
            $totalPrice = $products->sum("price");
            $order = Order::create([
                "user_id" => $user->id,
                "subtotal" => $totalPrice,
                "total" => $totalPrice,
                "status" => OrderStatus::PENDING
            ]);

            foreach ($products as $product) {
                OrderItem::create([
                    "product_id" => $product->id,
                    "order_id" => $order->id,
                    "price" => $product->price
                ]);
            }
        }
    }
}
