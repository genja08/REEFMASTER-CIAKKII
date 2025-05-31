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
        Schema::table('akkiis', function (Blueprint $table) {
            $table->string('jenis_akkii')->nullable()->after('cites_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akkiis', function (Blueprint $table) {
            $table->dropColumn('jenis_akkii');
        });
    }
};
