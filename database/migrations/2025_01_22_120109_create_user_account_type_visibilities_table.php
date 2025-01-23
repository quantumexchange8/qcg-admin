<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_account_type_visibilities', function (Blueprint $table) {
            // No ID column
            $table->unsignedBigInteger('account_type_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
    
            // Define foreign keys
            $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    
            // Add a unique constraint to prevent duplicate combinations
            $table->unique(['account_type_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_account_type_visibilities');
    }
};
