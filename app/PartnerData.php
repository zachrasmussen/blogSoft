<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerData extends Model
{
    /**
     * Allow for fields to be mass-fillable
     */
    protected $fillable = [
        'partner_id', 'name', 'value'
    ];

    /**
     * Custom table name
     */
    protected $table = 'partner_data';

    /**
     * Don't include timestamps
     */
    public $timestamps = false;

    /**
     * Return value if casted to string
     */
    public function __toString()
    {
        return $this->value;
    }
}
