<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan penugasan setiap record cybercrime ke cluster pada sebuah run.
 * Berelasi many-to-many: record × clustering_run → cluster_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clustering_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cybercrime_record_id')->constrained('cybercrime_records')->cascadeOnDelete();
            $table->unsignedTinyInteger('cluster');
            $table->double('pca_x')->nullable();
            $table->double('pca_y')->nullable();
            $table->timestamps();

            $table->unique(['clustering_run_id', 'cybercrime_record_id'], 'unique_run_record');
            $table->index(['clustering_run_id', 'cluster']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_assignments');
    }
};
