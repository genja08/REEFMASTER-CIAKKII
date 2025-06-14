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
            $table->string('jenis_cites')->nullable()->after('nomor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cites_documents', function (Blueprint $table) {
            $table->dropColumn('jenis_cites');
        });
    }
};
