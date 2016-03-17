<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingGatewaysTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gateways', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name', 50)->default('');
            $table->boolean('is_default')->default(false);
            $table->string('gateway_type', 50)->default('Omnipay');
            $table->string('description', 255)->nullable();
            $table->longText('extended_attributes')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gateways');
    }

}
