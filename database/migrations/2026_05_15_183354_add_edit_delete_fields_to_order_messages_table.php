<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('order_messages', function (Blueprint $table) {
        $table->timestamp('edited_at')->nullable();
        $table->timestamp('deleted_everyone_at')->nullable();
        $table->json('deleted_for')->nullable();
    });
}

public function down(): void
{
    Schema::table('order_messages', function (Blueprint $table) {
        $table->dropColumn(['edited_at', 'deleted_everyone_at', 'deleted_for']);
    });
}
};
