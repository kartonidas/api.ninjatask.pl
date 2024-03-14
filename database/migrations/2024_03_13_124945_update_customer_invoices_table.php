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
            $table->integer("external_invoicing_system_id")->nullable()->after("source");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("customer_invoices", function (Blueprint $table) {
            $table->dropColumn("external_invoicing_system_id");
        });
    }
};
