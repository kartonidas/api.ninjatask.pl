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
            $table->enum("system", ["app", "fakturownia", "wfirma", "infakt"])->default("app")->after("uuid");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("customer_invoices", function (Blueprint $table) {
            $table->dropColumn("system");
        });
    }
};
