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
        Schema::create('transactions_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('admin_fee');
            $table->string('transaction_code');
            $table->enum('status', [
                'Pending',
                'In Progress',
                'Completed',
                'Cancelled',
                'Expired',
                'Waiting',
                'On Hold',
            ])->default('Pending');
            $table->string('no_antrian');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('booking_id');
            $table->foreign('booking_id')->references('id')->on('booking')->onDelete('cascade');
            $table->uuid('clinic_id');
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions_user');
    }
};
