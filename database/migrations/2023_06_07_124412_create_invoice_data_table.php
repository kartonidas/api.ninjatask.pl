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
        Schema::create('invoice_data', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_city', 200);
            $table->string('name', 100);
            $table->string('address', 100);
            $table->string('street_no', 20);
            $table->string('apartment_no', 20);
            $table->string('post_code', 10);
            $table->string('city', 80);
            $table->string('nip', 15);
            $table->string('invoice_person', 60);
            $table->tinyInteger('active');
            $table->timestamps();
        });
        
        DB::table('invoice_data')->insert(
            array(
                'invoice_city' => 'Piotrków Tryb.',
                'name' => 'Artur Patura - netextend.pl',
                'address' => 'Łódzka',
                'street_no' => '46',
                'apartment_no' => '48',
                'post_code' => '97-300',
                'city' => 'Piotrków Tryb.',
                'nip' => 'PL7712671332',
                'invoice_person' => 'Artur Patura',
                'active' => true,
            )
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_data');
    }
};
