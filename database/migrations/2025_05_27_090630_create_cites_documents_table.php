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
        Schema::create('cites_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nomor');
            $table->date('issued_date');
            $table->date('expired_date');
            $table->string('airport_of_arrival');
            $table->foreignId('customer_id')->constrained('data_customers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cites_documents');
    }
};
