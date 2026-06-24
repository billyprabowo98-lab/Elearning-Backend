<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_kelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelas_id');
            $table->unsignedBigInteger('siswa_id');
            $table->year('tahun_ajaran');               // bisa beda dari kelas jika siswa naik kelas
            $table->timestamps();

            $table->foreign('kelas_id')
                  ->references('id')
                  ->on('kelas')
                  ->cascadeOnDelete();

            $table->foreign('siswa_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            // Satu siswa boleh masuk banyak kelas
            $table->index('siswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_kelas');
    }
};
