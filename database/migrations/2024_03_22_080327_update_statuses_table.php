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
        Schema::table("statuses", function (Blueprint $table) {
            $table->enum("task_state", ["open", "in_progress", "suspended", "closed"])->after("close_task")->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("statuses", function (Blueprint $table) {
            $table->dropColumn("task_state");
        });
    }
};
