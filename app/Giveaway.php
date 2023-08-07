<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Giveaway extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
