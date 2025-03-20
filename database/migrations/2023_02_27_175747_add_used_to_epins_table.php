<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsedToEpinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('epins', function (Blueprint $table) {
            $table->boolean('used')->default(0);
            $table->integer('payment_id')->nullable();
            $table->integer('approved_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('epins', function (Blueprint $table) {
            $table->dropColumn(['used','payment_id','approved_by']);
        });
    }
}
