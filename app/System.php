<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order', 'company_id', 'name', 'prefix', 'lock',
    ];


    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function components()
    {
        return $this->hasMany('App\SystemComponent');
    }
}
