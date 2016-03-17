<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateBillingTablePayments extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function($table){

            $table->increments('id');
            $table->integer('chargeable_id')->unsigned()->nullable();
            $table->integer('billable_id')->unsigned()->nullable();
            $table->integer('paymenttoken_id')->unsigned()->nullable();
            $table->integer('subscription_id')->unsigned()->nullable();
            $table->integer('gateway_id')->unsigned();
            $table->decimal('amount', 10,2)->default('0.00');
            $table->decimal('amount_usd', 10,2)->default('0.00');
            $table->string('currency', 20)->default('USD');
            $table->decimal('amount_not_refunded', 10,2)->default('0.00');
            $table->string('method', 50)->nullable();
            $table->string('service', 254);
            $table->string('platform', 254)->default('web');
            $table->timestamp('transaction_date')->nullable();
            $table->string('transaction_reference', 254)->nullable();
            $table->longText('transaction_details')->nullable();
            $table->longText('extended_attributes')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('paymenttoken_id')
                ->references('id')->on('paymenttokens')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('gateway_id')
                ->references('id')->on('gateways')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->index(['status', 'deleted_at', 'billable_id']);
            $table->index('chargeable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payments');
    }

}
