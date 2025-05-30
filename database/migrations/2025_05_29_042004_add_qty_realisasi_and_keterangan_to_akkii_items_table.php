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
        Schema::table('akkii_items', function (Blueprint $table) {
            $table->integer('qty_realisasi')->nullable()->after('qty_sisa');
            $table->text('keterangan')->nullable()->after('qty_realisasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akkii_items', function (Blueprint $table) {
            $table->dropColumn('qty_realisasi');
            $table->dropColumn('keterangan');
        });
    }
};
