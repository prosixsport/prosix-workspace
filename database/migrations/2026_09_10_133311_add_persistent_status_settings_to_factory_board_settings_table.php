<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory_board_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('factory_board_settings', 'status_options')) {
                $table->json('status_options')->nullable();
            }

            if (!Schema::hasColumn('factory_board_settings', 'custom_groups')) {
                $table->json('custom_groups')->nullable();
            }

            if (!Schema::hasColumn('factory_board_settings', 'default_group_overrides')) {
                $table->json('default_group_overrides')->nullable();
            }
        });
    }

    public function down(): void
    {
        /*
         * Intentionally left empty.
         * Existing shared status data must never be removed by a rollback.
         */
    }
};
