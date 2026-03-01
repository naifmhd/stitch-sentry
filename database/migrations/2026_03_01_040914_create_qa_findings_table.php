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
        Schema::create('qa_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_run_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key')->index();
            $table->string('severity')->index();
            $table->string('title');
            $table->text('message');
            $table->text('recommendation')->nullable();
            $table->json('evidence_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_findings');
    }
};
