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
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('type', ['invoice', 'receipt'])->after('order_id');
            $table->string("firstname", 100)->after("name")->nullable()->default(null);
            $table->string("lastname", 100)->after("firstname")->nullable()->default(null);
            $table->char("country", 2)->after("city")->nullable()->default(null);
            $table->char("lang", 2)->after("file")->default('pl');
            $table->string("currency", 5)->after("gross")->default('PLN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn("type");
            $table->dropColumn("firstname");
            $table->dropColumn("lastname");
            $table->dropColumn("country");
            $table->dropColumn("lang");
            $table->dropColumn("currency");
        });
    }
};
