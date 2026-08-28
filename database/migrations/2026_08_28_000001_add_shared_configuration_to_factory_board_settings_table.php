<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factory_board_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('factory_board_settings', 'column_order')) {
                $table->json('column_order')->nullable()->after('hidden_columns');
            }

            if (!Schema::hasColumn('factory_board_settings', 'status_options')) {
                $table->json('status_options')->nullable()->after('column_order');
            }

            if (!Schema::hasColumn('factory_board_settings', 'custom_groups')) {
                $table->json('custom_groups')->nullable()->after('status_options');
            }

            if (!Schema::hasColumn('factory_board_settings', 'default_group_overrides')) {
                $table->json('default_group_overrides')->nullable()->after('custom_groups');
            }
        });
    }

    public function down(): void
    {
        Schema::table('factory_board_settings', function (Blueprint $table) {
            $columns = [
                'column_order',
                'status_options',
                'custom_groups',
                'default_group_overrides',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('factory_board_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
