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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->enum("status", ["new", "expired", "active"])->nullable();
            $table->bigInteger('start');
            $table->bigInteger('end');
            $table->dateTime('expired')->nullable();
            $table->string('expired_reason', 100)->nullable();
            $table->timestamps();
            
            $table->index(['uuid', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
