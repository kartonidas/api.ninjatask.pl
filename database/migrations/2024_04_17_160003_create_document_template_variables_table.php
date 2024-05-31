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
        Schema::create('document_template_variables', function (Blueprint $table) {
            $table->id();
            $table->integer("document_template_id")->index();
            $table->string("variable", 200);
            $table->string("name", 200);
            $table->string("type", 40);
            $table->text("item_values")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_variables');
    }
};
