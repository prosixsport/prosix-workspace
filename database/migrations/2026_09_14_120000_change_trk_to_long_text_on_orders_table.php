<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->longText('trk')->nullable()->change();
        });
    }

    public function down(): void
    {
        /*
         * Intentionally left empty so long tracking data is never truncated
         * or lost during a rollback.
         */
    }
};
