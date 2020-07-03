<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->string("lat");
            $table->string("lon");
            $table->string("description");
            $table->integer("rooms_n");
            $table->integer("bedrooms_n");
            $table->integer("bathrooms_n");
            $table->integer("square_meters");
            $table->string("address");
            $table->string("image");
            $table->boolean("ad_not_active");

            // DA TOGLIERE
            $table->integer("views_n");

            $table->integer("sponsor_expire_time") -> unsigned();

            $table->bigInteger("user_id") -> unsigned() -> index();
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
        Schema::dropIfExists('apartments');
    }
}