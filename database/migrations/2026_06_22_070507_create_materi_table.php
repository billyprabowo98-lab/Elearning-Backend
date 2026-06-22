<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();        // path file di storage
            $table->string('file_original_name')->nullable(); // nama asli file upload
            $table->unsignedBigInteger('file_size')->nullable(); // ukuran dalam bytes
            $table->enum('tipe', ['pdf', 'gambar', 'video']); // jenis materi
            $table->string('link_video')->nullable();       // URL video jika tipe = video
            $table->unsignedBigInteger('mapel_id');
            $table->unsignedBigInteger('guru_id');
            $table->timestamps();

            $table->foreign('mapel_id')
                  ->references('id')
                  ->on('mata_pelajaran')
                  ->cascadeOnDelete();

            $table->foreign('guru_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->index(['mapel_id', 'tipe']);
            $table->index('guru_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
