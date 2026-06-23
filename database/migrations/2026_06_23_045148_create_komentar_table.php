<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komentar', function (Blueprint $table) {
            $table->id();
            $table->text('konten');
            $table->unsignedBigInteger('forum_topik_id');
            $table->unsignedBigInteger('user_id');          // penulis komentar
            $table->unsignedBigInteger('parent_id')->nullable(); // null = komentar utama, isi = balasan
            $table->timestamps();

            $table->foreign('forum_topik_id')
                  ->references('id')->on('forum_topik')
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('parent_id')
                  ->references('id')->on('komentar')
                  ->nullOnDelete(); // jika komentar induk dihapus, balasan tetap ada

            $table->index(['forum_topik_id', 'parent_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komentar');
    }
};
