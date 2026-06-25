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
        $table->unsignedBigInteger('reply_to_id')->nullable()->after('message');

        $table->foreign('reply_to_id')
            ->references('id')
            ->on('order_messages')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('order_messages', function (Blueprint $table) {
        $table->dropForeign(['reply_to_id']);
        $table->dropColumn('reply_to_id');
    });
}
};
