<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingRefundsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refunds', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('chargeable_id')->unsigned()->nullable();
            $table->integer('billable_id')->unsigned();
            $table->integer('payment_id')->unsigned();
            $table->integer('paymenttoken_id')->unsigned()->nullable();
            $table->integer('gateway_id')->unsigned();
            $table->decimal('amount', 10,2)->default('0.00');
            $table->string('service', 254)->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('transaction_reference', 254)->nullable();
            $table->longText('transaction_details')->nullable();
            $table->longText('extended_attributes')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->foreign('payment_id')
                ->references('id')->on('payments')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('paymenttoken_id')
                ->references('id')->on('paymenttokens')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('gateway_id')
                ->references('id')->on('gateways')
                ->onDelete('restrict')
                ->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('refunds');
    }

}
