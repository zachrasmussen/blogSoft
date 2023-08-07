<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    /**
     * Labels belong to a board
     */
    public function board()
    {
        return $this->belongsTo('App\TaskBoard');
    }

    /**
     * Labels can be applied to tasks
     */
    public function tasks()
    {
        return $this->belongsToMany('App\Task');
    }
}
