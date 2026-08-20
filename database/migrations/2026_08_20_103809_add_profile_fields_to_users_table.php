<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('avatar');
            }

            if (!Schema::hasColumn('users', 'about')) {
                $table->text('about')->nullable()->after('profile_photo');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'about',
                'phone',
            ]);
        });
    }
};
