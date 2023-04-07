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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->integer('project_id');
            $table->string('name', 250);
            $table->text('description')->default(null)->nullable();
            $table->integer("total")->default(0);
            $table->integer("total_billable")->default(0);
            $table->integer('created_user_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['uuid']);
            $table->index(['project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
