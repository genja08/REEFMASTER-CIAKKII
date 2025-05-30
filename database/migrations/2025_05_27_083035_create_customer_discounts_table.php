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
        Schema::create('customer_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('data_customers')->onDelete('cascade');
            $table->string('jenis_discount');
            $table->string('discount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_discounts', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['cites_document_id']);
        });
        Schema::dropIfExists('customer_discounts');
    }
};