<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions_user', function (Blueprint $table) {
            $table->enum('status', [
                'Pending',
                'In Progress',
                'Completed',
                'Cancelled',
                'Expired',
                'Waiting',
                'On Hold',
            ])->default('Pending');
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions_user', function (Blueprint $table) {
            //
        });
    }
};
