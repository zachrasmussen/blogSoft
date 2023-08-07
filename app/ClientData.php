<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientData extends Model
{
    /**
     * Custom table name
     */
    protected $table = 'client_data';

    /**
     * Attributes that are mass assignable
     */
    protected $fillable = [
        'client_id', 'name', 'value'
    ];

    /**
     * Return value if to string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Belongs to client
     */
    public function client()
    {
        return $this->belongsTo('App\Client');
    }
}
