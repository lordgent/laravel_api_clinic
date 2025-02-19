<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceInfoIdToTransactionsUserTable extends Migration
{
    public function up()
    {
        Schema::table('transactions_user', function (Blueprint $table) {
            $table->uuid('service_info_id')->nullable(); 

            $table->foreign('service_info_id')
                ->references('id')
                ->on('service_info')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('transactions_user', function (Blueprint $table) {
            $table->dropForeign(['service_info_id']);
            $table->dropColumn('service_info_id');
        });
    }
}
