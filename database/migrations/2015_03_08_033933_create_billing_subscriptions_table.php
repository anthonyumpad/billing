<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingSubscriptionsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('chargeable_id')->unsigned()->nullable();
            $table->integer('billable_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->integer('paymenttoken_id')->unsigned()->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('currency', 20)->default('USD');
            $table->integer('ran')->default(0);
            $table->integer('interval')->default(1);
            $table->string('interval_type')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('next_attempt')->nullable();
            $table->timestamp('last_attempt')->nullable();
            $table->boolean('defaulted')->default(false);
            $table->longText('data')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('paymenttoken_id');
            $table->index('billable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('subscriptions');
    }

}
