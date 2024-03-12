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
        Schema::table("customers", function (Blueprint $table) {
            $table->dropColumn("pesel");
            $table->dropColumn("document_type");
            $table->dropColumn("document_number");
            $table->dropColumn("document_extra");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("customers", function (Blueprint $table) {
            $table->string('pesel', 20)->nullable()->after('regon');
            $table->enum('document_type', ['id','passport'])->nullable()->after('pesel');
            $table->string('document_number', 100)->nullable()->after('document_type');
            $table->string('document_extra', 250)->nullable()->after('document_number');
        });
    }
};
