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
        Schema::create('numbering_lock', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->enum('type', ['customer_invoice']);
            $table->integer('val');
            $table->timestamps();
            
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbering_lock');
    }
};
