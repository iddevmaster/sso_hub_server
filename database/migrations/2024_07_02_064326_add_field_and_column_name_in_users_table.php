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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'prefix')) {
                $table->char('prefix', length: 100)->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'lname')) {
                $table->string('lname')->nullable()->after('name');
            }
            $table->renameColumn('email', 'username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'email');
        });
    }
};
