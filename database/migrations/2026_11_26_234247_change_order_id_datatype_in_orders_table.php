<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::rename('orders', 'orders_integer');

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('total')->default(0);
            $table->decimal('subtotal')->default(0);
            $table->string('status');
            $table->foreignId('discount_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('orders_integer')->get() as $order) {
            $uuid = (string) Str::uuid();
            $orderData = (array) $order;
            $orderData['id'] = $uuid;

            DB::table('orders')->insert($orderData);
            DB::table('order_items')
                ->where('order_id', $order->id)
                ->update(['order_id' => $uuid]);
        }

        Schema::drop('orders_integer');
        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::rename('orders', 'orders_uuid');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('total')->default(0);
            $table->decimal('subtotal')->default(0);
            $table->string('status');
            $table->foreignId('discount_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('orders_uuid')->get() as $order) {
            $orderData = (array) $order;
            unset($orderData['id']);

            DB::table('orders')->insert($orderData);
        }

        Schema::drop('orders_uuid');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
