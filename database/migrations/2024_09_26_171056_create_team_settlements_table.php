<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamp('transaction_start_at')->nullable();
            $table->timestamp('transaction_end_at')->nullable();
            $table->decimal('team_deposit', 13)->nullable();
            $table->decimal('team_withdrawal', 13)->nullable();
            $table->decimal('team_fee_percentage')->nullable();
            $table->decimal('team_fee', 13)->nullable();
            $table->decimal('team_balance', 13)->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_settlements');
    }
};
