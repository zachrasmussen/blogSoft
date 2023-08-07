<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    /**
     * Get meta data
     */
    public function data()
    {
        return $this->hasMany('App\PartnerData');
    }

    /**
     * Get specific data item
     */
    public function getData( $key )
    {
        return $this->data()->where('name', $key)->first();
    }

    /**
     * Get contacts
     */
    public function contacts()
    {
        return $this->hasMany('App\PartnerContact');
    }

    /**
     * Get posts
     */
    public function posts()
    {
        return $this->hasMany('App\PartnerPost');
    }

    /**
     * Get links
     */
    public function links()
    {
        return $this->hasMany('App\PartnerLink');
    }

    /**
     * Get docs
     */
    public function docs()
    {
        return $this->hasMany('App\PartnerDoc');
    }

    /**
     * Partners are assigned to companies
     */
    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
