<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialCount extends Model
{
    /**
     * Don't need timestamps
     */
    public $timestamps = false;

    /**
     * Make fields fillable
     */
    public $fillable = [
        'client_id', 'date', 'count'
    ];

    /**
     * Return value if to string
     */
    public function __toString()
    {
        return $this->count;
    }

    /**
     * Get parent client
     */
    public function client()
    {
        return $this->belongsTo('App\Client');
    }
}
