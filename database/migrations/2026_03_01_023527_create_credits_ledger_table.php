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
        Schema::create('credits_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('reason');
            $table->json('meta_json')->nullable();
            $table->string('idempotency_key');
            $table->timestamps();

            $table->unique(['organization_id', 'idempotency_key'], 'credits_ledger_org_idempotency_unique');
            $table->index(['organization_id', 'created_at'], 'credits_ledger_org_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits_ledger');
    }
};
