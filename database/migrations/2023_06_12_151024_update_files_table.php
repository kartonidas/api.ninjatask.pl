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
        Schema::table('files', function (Blueprint $table) {
            $table->index(['uuid'], 'uuid');
            $table->index(['type'], 'type');
            $table->index(['type', 'object_id'], 'type_object_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('uuid');
            $table->dropIndex('type');
            $table->dropIndex('type_object_id');
        });
    }
};
