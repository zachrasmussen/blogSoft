<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PartnerPost extends Model
{
    /**
     * Allow for fields to be mass-fillable
     */
    protected $fillable = [
        'partner_id', 'name', 'draft_due', 'final_due'
    ];

    /**
     * Don't include timestamps
     */
    public $timestamps = false;
}
