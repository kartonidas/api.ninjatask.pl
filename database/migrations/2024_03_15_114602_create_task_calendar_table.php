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
        Schema::create('task_calendar', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->integer("task_id")->index();
            $table->date("date");
            
            $table->index(['uuid', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_calendar');
    }
};
