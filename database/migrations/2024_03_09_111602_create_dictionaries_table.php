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
        Schema::create('dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->index();
            $table->string('type', 80);
            $table->tinyInteger('active')->nullable();
            $table->string('name', 80);
            $table->string('val', 250)->nullable();
            $table->timestamps();
            
            $table->index(['uuid', 'type'], 'index_uuid_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dictionaries');
    }
};
