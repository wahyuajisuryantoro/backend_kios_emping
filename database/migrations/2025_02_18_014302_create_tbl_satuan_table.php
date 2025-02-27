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
        Schema::create('tbl_satuan', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe_satuan', ['berat', 'unit', 'isi']);
            $table->string('nama_satuan', 50);
            $table->decimal('nilai_konversi', 10, 2);
            $table->string('simbol', 10);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes(); 
            $table->unique(['tipe_satuan', 'nama_satuan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_satuan');
    }
};
