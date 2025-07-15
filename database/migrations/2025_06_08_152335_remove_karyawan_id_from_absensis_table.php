<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveKaryawanIdFromAbsensisTable extends Migration
{
    public function up(): void
{
    Schema::table('absensis', function (Blueprint $table) {
        if (Schema::hasColumn('absensis', 'karyawan_id')) {
            $table->dropForeign(['karyawan_id']);
            $table->dropColumn('karyawan_id');
        }
    });
}

    public function down()
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->unsignedBigInteger('karyawan_id')->nullable();
        });
    }
}
