<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBillableTableForBillingService extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $billable_table = (! empty(Config::get('billing.billable_table'))) ? Config::get('billing.billable_table') : 'users';
        Schema::table($billable_table, function($table){
            $table->decimal('credit_balance',10,2)->default(0.00);
            $table->decimal('minimum_balance',10,2)->default(0.00);
            $table->decimal('autocharge_plan_points',10,2)->default(0.00);
            $table->string('autocharge_currency',10)->default('USD');
            $table->timestamp('last_autocharge_date')->nullable();
            $table->integer('autocharge_retries')->default(0);
            $table->tinyInteger('notification_sent')->default(0);
            $table->boolean('is_auto_renew')->default(0);
            $table->integer('card_expiry_notify_count')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $billable_table = (! empty(Config::get('billing.billable_table'))) ? Config::get('billing.billable_table') : 'users';
        Schema::table($billable_table, function($table){
            $table->dropColumn('credit_balance');
            $table->dropColumn('minimum_balance');
            $table->dropColumn('autocharge_plan_points');
            $table->dropColumn('autocharge_currency');
            $table->dropColumn('last_autocharge_date');
            $table->dropColumn('autocharge_retries');
            $table->dropColumn('notification_sent');
            $table->dropColumn('is_auto_renew');
            $table->dropColumn('card_expiry_notify_count');
        });
    }
}
