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
        Schema::table('design_files', function (Blueprint $table) {
            $table->index(['organization_id', 'created_at'], 'design_files_org_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('design_files', function (Blueprint $table) {
            $table->dropIndex('design_files_org_created_at_index');
        });
    }
};
