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
        Schema::create('task_times', function (Blueprint $table) {
            $table->id();
            $table->integer("task_id");
            $table->integer("user_id");
            $table->enum("status", ["active", "paused", "finished"])->default("active");
            $table->integer("started");
            $table->integer("finished")->nullable()->default(null);
            $table->integer("timer_started")->nullable();
            $table->integer("total")->default(0);
            $table->text("comment")->nullable();
            $table->tinyInteger("billable")->default(0);
            $table->timestamps();
            
            $table->index('task_id');
            $table->index(['task_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_times');
    }
};
