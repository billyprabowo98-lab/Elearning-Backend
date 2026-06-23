<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('isi');
            $table->unsignedBigInteger('user_id');          // pembuat pengumuman
            $table->unsignedBigInteger('kelas_id')->nullable(); // null = untuk semua kelas
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('kelas_id')
                  ->references('id')->on('kelas')
                  ->nullOnDelete(); // kelas dihapus → pengumuman tetap ada, kelas_id jadi null

            $table->index('user_id');
            $table->index('kelas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
