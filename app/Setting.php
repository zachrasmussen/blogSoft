<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'name', 'value'
    ];

    /**
     * Return value if to string
     */
    public function __toString()
    {
        return $this->value;
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
