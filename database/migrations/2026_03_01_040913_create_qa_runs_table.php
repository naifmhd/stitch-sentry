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
        Schema::create('qa_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_file_id')->constrained()->cascadeOnDelete();
            $table->string('preset')->default('custom');
            $table->string('status')->default('queued')->index();
            $table->string('stage')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->integer('score')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('error_code')->nullable();
            $table->string('support_id')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_runs');
    }
};
