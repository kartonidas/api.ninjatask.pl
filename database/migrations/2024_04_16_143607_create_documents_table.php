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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string("uuid", 64)->index();
            
            $table->integer("customer_id");
            $table->integer("task_id")->nullable();
            $table->string("title", 200);
            $table->text("content");
            $table->enum("type", ['agreement','annex','handover_protocol','other']);
            $table->integer("user_id");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
