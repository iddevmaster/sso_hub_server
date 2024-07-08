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
            if (!Schema::hasColumn('users', 'prefix_eng')) {
                $table->string('prefix_eng')->nullable();
            }
            if (!Schema::hasColumn('users', 'fname_eng')) {
                $table->string('fname_eng')->nullable();
            }
            if (!Schema::hasColumn('users', 'lname_eng')) {
                $table->string('lname_eng')->nullable();
            }
            if (!Schema::hasColumn('users', 'learning_status')) {
                $table->integer('learning_status')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'prefix_eng')) {
                $table->dropColumn('prefix_eng');
            }
            if (Schema::hasColumn('users', 'fname_eng')) {
                $table->dropColumn('fname_eng');
            }
            if (Schema::hasColumn('users', 'lname_eng')) {
                $table->dropColumn('lname_eng');
            }
            if (Schema::hasColumn('users', 'learning_status')) {
                $table->dropColumn('learning_status');
            }
        });
    }
};
