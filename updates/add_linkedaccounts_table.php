<?php namespace Tlokuus\LoginWithSocial\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddLinkedAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('tlokuus_linked_social_accounts', function($table){
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('provider');
            $table->string('identifier');
            $table->text('profile_data');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');    
            $table->unique(['provider','identifier']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('tlokuus_linked_social_accounts');

    }
}