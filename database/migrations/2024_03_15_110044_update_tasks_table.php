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
            $table->char("start_date_time", 5)->after("start_date")->nullable();
            $table->date("end_date")->after("start_date_time")->nullable();
            $table->char("end_date_time", 5)->after("end_date")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("tasks", function (Blueprint $table) {
            $table->dropColumn("start_date_time");
            $table->dropColumn("end_date");
            $table->dropColumn("end_date_time");
        });
    }
};
