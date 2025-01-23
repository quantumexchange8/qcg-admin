<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->unsignedInteger('account_group_id')->nullable();
            $table->string('account_group')->nullable();
            $table->unsignedBigInteger('group');
            $table->string('category')->nullable();
            $table->string('color', 100)->nullable();
            $table->double('minimum_deposit')->default(0);
            $table->integer('leverage')->default(0);
            $table->string('currency');
            $table->integer('allow_create_account');
            $table->integer('maximum_account_number')->nullable();
            $table->string('type');
            $table->string('commission_structure');
            $table->string('trade_open_duration');
            $table->decimal('account_group_balance', 15, 2)->nullable();
            $table->decimal('account_group_equity', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('image')->nullable();
            $table->json('descriptions')->nullable();

            $table->string('visible_to')->nullable();
            
            $table->string('promotion_title')->nullable();
            $table->text('promotion_description')->nullable();
            $table->string('promotion_period_type')->nullable();
            $table->string('promotion_period')->nullable();
            $table->string('promotion_type')->nullable();
            $table->decimal('target_amount', 15, 2)->nullable();
            $table->string('bonus_type')->nullable();
            $table->string('bonus_amount_type')->nullable();
            $table->decimal('bonus_amount', 15, 2)->nullable();
            $table->decimal('maximum_bonus_cap', 15, 2)->nullable();
            $table->string('applicable_deposit')->nullable();
            $table->string('credit_withdraw_policy')->nullable();
            $table->string('credit_withdraw_date_period')->nullable();

            $table->string('status')->default('inactive');
            $table->boolean('delete')->default(0);
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->boolean('show_register')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('account_types');
    }
    };
