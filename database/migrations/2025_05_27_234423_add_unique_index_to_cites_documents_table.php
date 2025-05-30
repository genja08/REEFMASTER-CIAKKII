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
        Schema::table('cites_documents', function (Blueprint $table) {
            $table->unique('nomor', 'cites_documents_nomor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cites_documents', function (Blueprint $table) {
            $table->dropUnique('cites_documents_nomor_unique');
        });
    }
};
