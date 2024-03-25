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
        Schema::create('task_history_fields', function (Blueprint $table) {
            $table->id();
            $table->integer("task_history_id")->index();
            $table->string("field", 150);
            $table->text("value");
            $table->text("old_value")->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_history_fields');
    }
};
