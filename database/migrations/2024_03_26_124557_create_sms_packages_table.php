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
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->index();
            $table->enum('status', ['active', 'expired', 'used'])->default('active');
            $table->integer('allowed');
            $table->integer('used');
            $table->integer('expired')->nullable()->default(null);
            $table->timestamps();
            
            $table->index(["uuid", "status"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_packages');
    }
};
