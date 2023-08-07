<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskList extends Model
{
    public function board()
    {
        return $this->belongsTo('App\TaskBoard');
    }

    public function tasks()
    {
        return $this->hasMany('App\Task');
    }
}
