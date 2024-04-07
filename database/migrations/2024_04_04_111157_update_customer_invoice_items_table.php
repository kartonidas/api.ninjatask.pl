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
        Schema::table("customer_invoice_items", function (Blueprint $table) {
            $table->integer('task_id')->after("total_gross_amount_discount")->nullable()->default(null);
            $table->index("task_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("customer_invoice_items", function (Blueprint $table) {
            $table->dropColumn('task_id');
        });
    }
};
