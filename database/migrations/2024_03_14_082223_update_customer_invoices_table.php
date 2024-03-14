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
        Schema::table("customer_invoices", function (Blueprint $table) {
            $table->dropColumn("payment_type_id");
            $table->string("payment_type", 50)->after("payment_date");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("customer_invoices", function (Blueprint $table) {
            $table->dropColumn("payment_type");
            $table->integer("payment_type_id")->after("payment_date");
        });
    }
};
