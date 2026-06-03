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
    Schema::table('invoices', function (Blueprint $table) {
        $table->boolean('card_payment_active')->default(false);
        $table->boolean('bank_account_allowed')->default(false);
        $table->string('invoice_attachment')->nullable();
        $table->string('stripe_payment_url')->nullable();
    });
}

public function down(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropColumn([
            'card_payment_active',
            'bank_account_allowed',
            'invoice_attachment',
            'stripe_payment_url',
        ]);
    });
}
};
