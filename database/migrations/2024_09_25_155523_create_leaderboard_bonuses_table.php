<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leaderboard_bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('leaderboard_profile_id')->nullable();
            $table->decimal('target_amount', 13)->nullable();
            $table->decimal('achieved_percentage')->nullable();
            $table->decimal('achieved_amount', 13)->nullable();
            $table->decimal('incentive_rate')->nullable();
            $table->decimal('incentive_amount')->nullable();
            $table->integer('incentive_month')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('leaderboard_profile_id')
                ->references('id')
                ->on('leaderboard_profiles')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_bonuses');
    }
};
