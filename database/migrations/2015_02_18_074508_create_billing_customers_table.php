<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingCustomersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('gateway_id')->unsigned();
            $table->integer('billable_id')->unsigned();
            $table->string('token', 255)->nullable();
            $table->longText('extended_attributes')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('gateway_id')
                ->references('id')->on('gateways')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('customers');
    }

}
