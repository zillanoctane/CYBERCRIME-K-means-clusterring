<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema utama: laporan tindak pidana siber.
 *
 * Mengacu pada taksonomi UU ITE No. 11/2008 jo. UU No. 19/2016 dan UU Perlindungan
 * Data Pribadi No. 27/2022, serta kategorisasi yang dipakai oleh Direktorat
 * Tindak Pidana Siber Bareskrim Polri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cybercrime_records', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_laporan', 64)->unique();
            $table->date('tanggal_kejadian')->index();
            $table->date('tanggal_laporan');

            // Klasifikasi
            $table->string('jenis_kejahatan', 80)->index();
            $table->string('sub_jenis', 120)->nullable();
            $table->string('modus_operandi', 120)->index();
            $table->string('platform', 60)->nullable()->comment('WhatsApp, Instagram, Email, Website, dst.');

            // Lokasi
            $table->string('provinsi', 64)->index();
            $table->string('kota_kabupaten', 80)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Profil korban
            $table->unsignedSmallInteger('usia_korban')->nullable();
            $table->enum('jenis_kelamin_korban', ['L', 'P', 'TD'])->default('TD');
            $table->string('pekerjaan_korban', 80)->nullable();
            $table->enum('pendidikan_korban', ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'TD'])->default('TD');

            // Dampak
            $table->unsignedBigInteger('estimasi_kerugian')->default(0)->comment('IDR');
            $table->unsignedSmallInteger('jumlah_korban')->default(1);
            $table->enum('tingkat_keparahan', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('sedang')->index();

            // Status penanganan
            $table->enum('status_kasus', ['baru', 'dalam_penyelidikan', 'p21', 'selesai', 'dihentikan'])->default('baru');
            $table->boolean('tersangka_teridentifikasi')->default(false);

            // Sumber & catatan
            $table->string('sumber_data', 80)->default('Laporan Masyarakat');
            $table->text('keterangan')->nullable();

            // Audit
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provinsi', 'tanggal_kejadian']);
            $table->index(['jenis_kejahatan', 'tanggal_kejadian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cybercrime_records');
    }
};
