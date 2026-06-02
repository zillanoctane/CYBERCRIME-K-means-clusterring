<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setiap eksekusi K-Means clustering disimpan sebagai sebuah ``run``.
 *
 * Konfigurasi (fitur, parameter, filter), output (metrik, profil), dan
 * timestamp disimpan agar hasil dapat direproduksi (reproducible research).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clustering_runs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 160);
            $table->text('deskripsi')->nullable();

            // Konfigurasi
            $table->unsignedTinyInteger('n_clusters');
            $table->json('fitur_numerik');
            $table->json('fitur_kategorikal');
            $table->string('scaler', 16)->default('standard');
            $table->json('filter')->nullable()->comment('Rentang tanggal, jenis, provinsi, dst.');
            $table->unsignedInteger('random_state')->default(42);

            // Snapshot ukuran data
            $table->unsignedInteger('jumlah_data');

            // Output ringkas
            $table->double('inertia')->nullable();
            $table->double('silhouette')->nullable();
            $table->double('davies_bouldin')->nullable();
            $table->double('calinski_harabasz')->nullable();
            $table->unsignedSmallInteger('iterations')->nullable();

            // Detail lengkap (profiles, projection, importance) dalam JSON
            $table->longText('hasil_json')->nullable();

            // Tipe analisis
            $table->enum('mode', ['manual', 'auto'])->default('manual')->comment('auto = K ditentukan via Elbow');
            $table->json('elbow_points')->nullable();

            $table->enum('status', ['draft', 'sukses', 'gagal'])->default('draft')->index();
            $table->text('error_message')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clustering_runs');
    }
};
