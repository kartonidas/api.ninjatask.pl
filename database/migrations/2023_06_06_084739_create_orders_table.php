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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->enum("status", ["new", "finished"])->default("new");
            $table->enum("type", ["subscription"])->default("subscription");
            $table->decimal("unit_price", 8, 2);
            $table->decimal("unit_price_gross", 8, 2);
            $table->integer("quantity");
            $table->decimal("amount", 8, 2);
            $table->decimal("vat", 8, 2);
            $table->decimal("gross", 8, 2);
            $table->string('name', 200);
            $table->integer('months')->default(1);
            $table->integer("subscription_id")->nullable()->default(null);
            $table->dateTime('paid')->nullable()->default(null);
            $table->integer("invoice_id")->nullable()->default(null);
            $table->timestamps();
            
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
