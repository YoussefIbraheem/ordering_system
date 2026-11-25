<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId("discount_id")->nullable(true);
            $table->removeColumn("discount_amount");
            $table->removeColumn("discount_type");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->removeColumn("discount_id");
            $table->decimal("discount_amount")->nullable();
            $table->string("discount_type")->nullable();
        });
    }
};
