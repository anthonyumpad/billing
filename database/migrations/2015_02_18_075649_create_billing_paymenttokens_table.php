<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingPaymenttokensTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymenttokens', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('customer_id')->unsigned();
            $table->integer('billable_id')->unsigned();
            $table->string('token', 255)->nullable();
            $table->boolean('is_default')->default(true);
            $table->longText('extended_attributes')->nullable();
            $table->date('start_date')->default('0000-00-00');
            $table->date('expiry_date')->default('0000-00-00');
            $table->string('brand', 255)->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');

            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->onDelete('cascade')
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
        Schema::drop('paymenttokens');
    }

}
