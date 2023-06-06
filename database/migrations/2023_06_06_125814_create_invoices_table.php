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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64);
            $table->integer("order_id");
            $table->integer("number");
            $table->integer("month");
            $table->integer("year");
            $table->string("full_number", 50);
            $table->date("date");
            $table->string("nip", 20)->nullable();
            $table->string("name", 200)->nullable();
            $table->string("street", 80)->nullable();
            $table->string("house_no", 20)->nullable();
            $table->string("apartment_no", 20)->nullable();
            $table->string("zip", 10)->nullable();
            $table->string("city", 120)->nullable();
            $table->decimal("amount", 8, 2);
            $table->decimal("gross", 8, 2);
            $table->text("items");
            $table->tinyInteger("generated")->default(0);
            $table->string("file", 250)->nullable();
            $table->timestamps();
            
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
