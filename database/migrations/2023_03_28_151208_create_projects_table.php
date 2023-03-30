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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->string('name', 250);
            $table->text('location')->default(null)->nullable();
            $table->text('description')->default(null)->nullable();
            $table->text('owner')->default(null)->nullable();
            $table->timestamps();
            
            $table->index(['uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
