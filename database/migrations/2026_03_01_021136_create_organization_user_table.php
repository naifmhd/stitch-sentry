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
        Schema::create('organization_user', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index('user_id', 'org_user_user_id_idx');
            $table->foreign('organization_id', 'org_user_org_id_fk')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('user_id', 'org_user_user_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
