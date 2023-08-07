<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * A task belongs to a task list
     */
    public function list()
    {
        return $this->belongsTo('App\TaskList');
    }

    /**
     * Tasks can have labels
     */
    public function labels()
    {
        return $this->belongsToMany('App\TaskLabel');
    }
}
