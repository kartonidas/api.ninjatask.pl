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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->enum('type', ['projects', 'tasks', 'task_comments']);
            $table->integer('object_id');
            $table->integer('user_id');
            $table->string('filename', 250);
            $table->string('orig_name', 250);
            $table->string('extension', 5);
            $table->integer('size');
            $table->text('description')->nullable()->default(null);
            $table->integer('sort');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
