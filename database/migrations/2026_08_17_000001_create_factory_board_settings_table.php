<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factory_board_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_assign_all_owners')->default(false);
            $table->json('hidden_columns')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_board_settings');
    }
};
