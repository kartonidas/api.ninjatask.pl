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
        Schema::rename("project_deleted_tasks", "soft_deleted_objects");
        Schema::table('soft_deleted_objects', function (Blueprint $table) {
            $table->dropColumn("project_id");
            $table->dropColumn("task_id");
            $table->string("source", 50)->after("id");
            $table->integer("source_id")->after("source");
            $table->string("object", 50)->after("source_id");
            $table->integer("object_id")->after("object");
            
            $table->index(['source', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename("soft_deleted_objects", "project_deleted_tasks");
        Schema::table('project_deleted_tasks', function (Blueprint $table) {
            $table->integer("project_id")->after("id");
            $table->integer("task_id")->after("project_id");
            $table->dropColumn("source");
            $table->dropColumn("source_id");
            $table->dropColumn("object");
            $table->dropColumn("object_id");
        });
    }
};
