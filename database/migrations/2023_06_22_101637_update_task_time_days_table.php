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
        Schema::table('task_time_days', function (Blueprint $table) {
            $table->date("date")->change();
            $table->dropColumn("period");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_time_days', function (Blueprint $table) {
            $table->string("date", 10)->change();
            $table->string("period", 5)->default("day")->after("date");
        });
    }
};
