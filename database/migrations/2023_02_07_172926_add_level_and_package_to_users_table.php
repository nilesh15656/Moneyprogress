<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLevelAndPackageToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->unsignedBigInteger('level_id');
            // $table->foreign('level_id')->references('id')->on('levels');
            // $table->unsignedBigInteger('package_id');
            // $table->foreign('package_id')->references('id')->on('packages');
            $table->integer('level_id')->nullable();
            $table->integer('package_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['package_id','level_id']);
        });
    }
}
