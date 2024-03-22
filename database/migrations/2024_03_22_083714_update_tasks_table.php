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
        Schema::table("tasks", function (Blueprint $table) {
            $table->enum("state", ["open", "in_progress", "suspended", "closed"])->after("uuid")->default("open");
            
            $table->datetime("completed_at")->change()->nullable()->default(null);
            $table->renameColumn("completed_at", "closed_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table("tasks", function (Blueprint $table) {
            $table->dropColumn("state");
            $table->renameColumn("closed_at", "completed_at");
        });
    }
};
