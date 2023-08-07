<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemComponent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'system_id', 'name', 'url', 'status', 'type_id', 'num',
    ];

    public function system()
    {
       return $this->belongsTo('App\System');
    }
}
