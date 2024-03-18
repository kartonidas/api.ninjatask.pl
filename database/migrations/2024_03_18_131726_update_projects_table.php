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
        Schema::table("projects", function (Blueprint $table) {
            $table->text("address")->after("owner")->nullable();
            $table->string("lat", 100)->after("address")->nullable();
            $table->string("lon", 100)->after("lat")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("projects", function (Blueprint $table) {
            $table->dropColumn("address");
            $table->dropColumn("lat");
            $table->dropColumn("lon");
        });
    }
};
