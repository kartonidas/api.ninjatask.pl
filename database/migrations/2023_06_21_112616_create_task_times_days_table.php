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
        Schema::create('task_time_days', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->integer("task_time_id");
            $table->integer("project_id");
            $table->integer("task_id");
            $table->integer("user_id");
            $table->string("date", 10);
            $table->string("period", 5)->default("day");
            $table->integer("total");
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('uuid');
            $table->index('task_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_time_days');
    }
};
