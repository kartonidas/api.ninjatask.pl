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
        Schema::create('sale_register', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->string('name', 100);
            $table->enum('type', ['proforma', 'invoice', 'correction']);
            $table->string('mask', 100);
            $table->enum('continuation', ['month', 'year', 'continuation']);
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();
            
            $table->index(["uuid", "type"]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_register');
    }
};
