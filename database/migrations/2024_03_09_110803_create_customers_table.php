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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->index();
            $table->enum('role', ['customer'])->default('customer');
            $table->enum('type', ['firm', 'person'])->default('person');
            $table->string('name', 200);
            $table->string('street', 80)->nullable();
            $table->string('house_no', 20)->nullable();
            $table->string('apartment_no', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('zip', 10)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('nip', 20)->nullable();
            $table->string('regon', 15)->nullable();
            $table->string('pesel', 20)->nullable();
            $table->enum('document_type', ['id','passport'])->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('document_extra', 250)->nullable();
            $table->text('comments')->nullable();
            $table->tinyInteger('send_sms')->default(1);
            $table->tinyInteger('send_email')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(["uuid", "role"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
