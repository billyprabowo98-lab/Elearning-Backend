<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');          // penerima notifikasi
            $table->string('judul');
            $table->text('pesan');
            $table->enum('tipe', [
                'komentar_baru',        // ada komentar di topik saya
                'balasan_komentar',     // ada yang balas komentar saya
                'topik_baru',           // ada topik baru di mapel yang diikuti
            ]);
            $table->morphs('notifiable'); // polymorphic: bisa ke forum_topik atau komentar
            $table->timestamp('dibaca_at')->nullable();     // null = belum dibaca
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'dibaca_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
