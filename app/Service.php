<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Location;

class Service extends Model
{
  public function locations(){
    return $this -> belongsToMany(Location::class);
  }
}
