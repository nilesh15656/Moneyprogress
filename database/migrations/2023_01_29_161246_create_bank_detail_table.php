<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_type')->nullable();
            $table->string('bank_ac_number')->nullable();
            $table->string('bank_holder_name')->nullable();
            $table->string('bank_address')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->string('cheque_image')->nullable();
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
        Schema::dropIfExists('bank_detail');
    }
}
