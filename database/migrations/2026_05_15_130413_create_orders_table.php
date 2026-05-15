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
       Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('po')->nullable();
    $table->date('ship_date')->nullable();
    $table->string('status')->default('Pending');
    $table->string('status_color')->default('#fdab3d');
    $table->string('trk')->nullable();
    $table->string('payment')->default('0 % Paid');
    $table->decimal('payment_received', 10, 2)->default(0);
    $table->decimal('payment_balance', 10, 2)->default(0);
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
