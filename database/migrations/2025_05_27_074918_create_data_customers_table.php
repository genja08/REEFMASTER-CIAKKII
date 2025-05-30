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
        Schema::create('data_customers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_address');
            $table->string('office_phone')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('country');
            $table->string('email')->nullable();
            $table->string('tujuan')->nullable();
            $table->string('airport_of_arrival')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_customers');
    }
};
