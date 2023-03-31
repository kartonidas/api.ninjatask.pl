<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('firm_id')->default(0);
            $table->string('firstname', 100)->nullable();
            $table->string('lastname', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('activated')->default(0);
            $table->tinyInteger('deleted')->default(0);
            $table->tinyInteger('owner')->default(0);
            $table->tinyInteger('superuser')->default(0);
            $table->integer('user_permission_id')->nullable()->default(null);
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(['email']);
            $table->index(['activated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
