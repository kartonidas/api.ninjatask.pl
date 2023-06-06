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
        Schema::table('firms', function (Blueprint $table) {
            $table->string('firstname', 100)->nullable()->default(null)->after('identifier');
            $table->string('lastname', 100)->nullable()->default(null)->after('firstname');
            $table->string('email', 255)->nullable()->default(null)->after('lastname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            $table->dropColumn("firstname");
            $table->dropColumn("lastname");
            $table->dropColumn("email");
        });
    }
};
