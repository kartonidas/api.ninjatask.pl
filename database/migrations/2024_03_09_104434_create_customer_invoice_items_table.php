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
        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_invoice_id');
            $table->string('gtu', 10)->nullable()->default(null);
            $table->string('name', 200);
            $table->decimal('quantity', 8, 2);
            $table->string('unit_type', 50)->nullable()->default(null);
            $table->decimal('net_amount', 12, 4)->nullable()->default(0);
            $table->decimal('total_net_amount', 12, 4)->nullable()->default(0);
            $table->string('vat_value', 20)->nullable()->default(null);
            $table->decimal('gross_amount', 12, 4)->nullable()->default(0);
            $table->decimal('total_gross_amount', 12, 4)->nullable()->default(0);
            $table->decimal('vat_amount', 12, 4)->nullable()->default(0);
            $table->decimal('discount', 12, 4)->nullable()->default(0);
            $table->decimal('net_amount_discount', 12, 4)->nullable()->default(0);
            $table->decimal('gross_amount_discount', 12, 4)->nullable()->default(0);
            $table->decimal('total_net_amount_discount', 12, 4)->nullable()->default(0);
            $table->decimal('total_gross_amount_discount', 12, 4)->nullable()->default(0);
            $table->timestamps();
            
            $table->index("customer_invoice_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_items');
    }
};
