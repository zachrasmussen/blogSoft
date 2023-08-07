<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Website Data Model
 */

class Website extends Model
{
    //
    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
