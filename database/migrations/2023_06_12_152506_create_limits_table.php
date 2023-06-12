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
        Schema::create('limits', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->integer('tasks');
            $table->integer('projects');
            $table->integer('space');
            $table->timestamps();
            
            $table->index(['uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limits');
    }
};
