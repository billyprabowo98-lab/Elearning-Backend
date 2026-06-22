<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas');               // contoh: X IPA 1
            $table->string('tingkat');                  // contoh: X, XI, XII
            $table->string('jurusan')->nullable();      // contoh: IPA, IPS, RPL
            $table->unsignedBigInteger('guru_id')->nullable(); // wali kelas
            $table->year('tahun_ajaran');               // contoh: 2024
            $table->timestamps();

            $table->foreign('guru_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // Satu kelas unik per nama + tahun ajaran
            $table->unique(['nama_kelas', 'tahun_ajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
