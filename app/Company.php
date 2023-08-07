<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /**
     * Get all users
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->using('App\CompanyUser')->withPivot(['role']);
    }

    /**
     * oAuth2 Clients associated with website
     */
    public function clients()
    {
        return $this->hasMany('App\Client');
    }

    /**
     * Get specific client
     */
    public function getClient( $type )
    {
        if($type == "all"){
            return $this->clients()->where('type','!=','analytics')->where('type','!=','facebook_user')->get();
        }
        return $this->clients()->where( 'type', $type )->first();
    }

    /**
     * Websites
     */
    public function websites()
    {
        return $this->hasMany('App\Website');
    }

    /**
     * Partners belonging to company
     */
    public function partners()
    {
        return $this->hasMany('App\Partner');
    }

    /**
     * Get Sponsors
     */
    public function getSponsors()
    {
        return $this->partners()->where('type', 'sponsor')->orderBy('status','ASC')->get();
    }

    /**
     * Get Affiliates
     */
    public function getAffiliates()
    {
        return $this->partners()->where('type', 'affiliate')->orderBy('status','ASC')->get();
    }

    /**
     * Task Boards belonging to company
     */
    public function boards()
    {
        return $this->hasMany('App\TaskBoard');
    }

    /**
     * Systems
     */
    public function systems()
    {
        return $this->hasMany('App\System');
    }

    /**
     * System Notes
     */
    public function systemNotes()
    {
        return $this->hasOne('App\SystemNotes');
    }

    /**
     * Quick links belonging to website
     */
    public function quickLinks()
    {
        return $this->hasMany('App\QuickLink');
    }

    /**
     * Notes
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }
}
