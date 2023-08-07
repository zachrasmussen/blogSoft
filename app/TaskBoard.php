<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Task Board Data Model
 */

class TaskBoard extends Model
{
    /**
     * Boards have lists
     */
    public function lists()
    {
        return $this->hasMany('App\TaskList');
    }

    /**
     * Boards have labels
     */
    public function labels()
    {
        return $this->hasMany('App\TaskLabel');
    }

    /**
     * Boards are assigned to users
     */
    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
