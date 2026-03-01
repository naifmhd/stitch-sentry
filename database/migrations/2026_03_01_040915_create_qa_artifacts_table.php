<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qa_artifacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_run_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->index();
            $table->string('storage_path');
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_artifacts');
    }
};
