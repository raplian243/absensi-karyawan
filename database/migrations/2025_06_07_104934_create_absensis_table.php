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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');

            // Absen masuk
            $table->time('jam_masuk')->nullable();
            $table->string('lokasi_masuk')->nullable();
            $table->string('foto_masuk')->nullable();

            // Absen pulang
            $table->time('jam_pulang')->nullable();
            $table->string('lokasi_pulang')->nullable();
            $table->string('foto_pulang')->nullable();

            // Lembur
            $table->boolean('lembur')->default(false);
            $table->time('jam_lembur')->nullable();
            $table->time('waktu_lembur_selesai')->nullable();
            $table->string('durasi_lembur')->nullable();

            // Kehadiran
            $table->enum('status', ['hadir', 'izin', 'alpa'])->default('hadir');
            $table->string('keterangan')->nullable(); // contoh: Tepat Waktu / Terlambat

            $table->timestamps();

            // Relasi & constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'tanggal']); // Satu absen per user per hari
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
