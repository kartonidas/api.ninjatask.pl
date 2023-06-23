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
        Schema::table('firm_invoicing_data', function (Blueprint $table) {
            $table->char("country", 2)->after("zip");
            $table->dropColumn("country_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firm_invoicing_data', function (Blueprint $table) {
            $table->dropColumn("country");
            $table->integer('country_id')->nullable()->default(null)->after("zip");
        });
    }
};
