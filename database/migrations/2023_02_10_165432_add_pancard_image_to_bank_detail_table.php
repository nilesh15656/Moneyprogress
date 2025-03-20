<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPancardImageToBankDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_detail', function (Blueprint $table) {
            $table->string('pancard_image')->nullable();
            $table->string('status')->default('unapprove')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_detail', function (Blueprint $table) {
            $table->dropColumn(['pancard_image','status'])->nullable();
        });
    }
}
