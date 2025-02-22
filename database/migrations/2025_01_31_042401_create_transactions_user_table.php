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
            $table->string('price')->nullable();
            $table->enum('status', [
              'waiting',
              'called',
              'missed',
              'completed',
              'cancel',
              'active'
            ])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->string('no_antrian');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('clinic_id');
            $table->foreign('clinic_id')->references('id')->on('clinics')->onDelete('cascade');
            $table->timestamps();
            $table->date('active_date')->nullable();
            $table->uuid('service_info_id');
            $table->date('booking_date')->nullable();
            $table->foreign('service_info_id')->references('id')->on('clinics')->onDelete('cascade');
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
