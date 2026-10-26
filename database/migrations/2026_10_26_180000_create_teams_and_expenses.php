<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained();
            $table->boolean('is_manager')->default(false);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('description');
            $table->unsignedInteger('amount_cents');
            // pending, approved or rejected
            $table->string('status')->default('pending');
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('is_manager');
        });
        Schema::dropIfExists('teams');
    }
};
