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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum("status", ["new", "finished", "canceled"])->default("new");
            $table->string("type", 50);
            $table->string("md5", 32);
            $table->integer("order_id");
            $table->decimal("amount", 8, 2);
            $table->dateTime("finished")->nullable()->default(null);
            $table->timestamps();
            
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
