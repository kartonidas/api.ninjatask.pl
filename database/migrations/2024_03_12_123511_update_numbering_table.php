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
        Schema::table("numbering", function (Blueprint $table) {
            $table->dropColumn("sale_register_id");
            $table->string("document_type", 30)->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("numbering", function (Blueprint $table) {
            $table->dropColumn("document_type");
            $table->integer("sale_register_id")->nullable();
        });
    }
};
