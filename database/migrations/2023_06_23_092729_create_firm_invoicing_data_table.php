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
        Schema::create('firm_invoicing_data', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->enum('type', ['invoice', 'receipt']);
            $table->string("nip", 20)->nullable();
            $table->string('name', 200)->nullable();
            $table->string('firstname', 100)->nullable();
            $table->string('lastname', 100)->nullable();
            $table->string('street', 80);
            $table->string('house_no', 20);
            $table->string('apartment_no', 20)->nullable();
            $table->string('city', 120);
            $table->string('zip', 10);
            $table->integer('country_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_invoicing_data');
    }
};
