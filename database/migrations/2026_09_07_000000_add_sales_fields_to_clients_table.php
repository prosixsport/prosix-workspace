<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('source_of_contact', 100)->nullable()->after('company');
            $table->string('country', 100)->nullable()->after('source_of_contact');
            $table->string('city_state', 150)->nullable()->after('country');
            $table->longText('price_list')->nullable()->after('address');
            $table->json('price_list_files')->nullable()->after('price_list');
            $table->string('lead_status', 50)->default('new')->after('price_list_files');
            $table->string('team_name')->nullable()->after('lead_status');
            $table->longText('notes')->nullable()->after('team_name');
        });
    }

    public function down(): void
    {
        $columns = [
            'source_of_contact',
            'country',
            'city_state',
            'price_list',
            'price_list_files',
            'lead_status',
            'team_name',
            'notes',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn('clients', $column)
        ));

        if ($existingColumns !== []) {
            Schema::table('clients', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
