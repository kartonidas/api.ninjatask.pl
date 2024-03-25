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
        Schema::create('task_history', function (Blueprint $table) {
            $table->id();
            $table->integer("task_id")->index();
            $table->integer("object_id")->nullable()->default(null);
            $table->string("operation", 20);
            $table->integer("user_id");
            $table->text("extra")->nullable()->default(null);
            $table->timestamp("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_history');
    }
};
