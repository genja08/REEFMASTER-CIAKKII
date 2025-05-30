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
        Schema::create('akkiis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cites_document_id')->constrained('cites_documents')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('data_customers')->onDelete('cascade');
            $table->string('nomor_cites')->unique();
            $table->string('company_address')->nullable();
            $table->string('country')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('tujuan')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('airport_of_arrival')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->date('tanggal_ekspor')->nullable();
            $table->string('no_awb')->nullable();
            $table->string('no_aju')->nullable();
            $table->string('no_pendaftaran')->nullable();
            $table->date('tanggal_pendaftaran')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akkiis');
    }
};
