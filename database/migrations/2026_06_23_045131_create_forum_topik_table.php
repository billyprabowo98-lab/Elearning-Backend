<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_topik', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('konten');
            $table->unsignedBigInteger('user_id');          // pembuat topik
            $table->unsignedBigInteger('mapel_id')->nullable(); // topik terkait mapel (opsional)
            $table->enum('status', ['aktif', 'ditutup'])->default('aktif');
            $table->unsignedInteger('jumlah_dilihat')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('mapel_id')->references('id')->on('mata_pelajaran')->nullOnDelete();

            $table->index('user_id');
            $table->index('mapel_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_topik');
    }
};
