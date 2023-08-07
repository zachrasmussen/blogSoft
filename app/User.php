<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * User Data Model
 */

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * User is attached to Multiple Websites
     */
    public function companies()
    {
        return $this->belongsToMany('App\Company')->using('App\CompanyUser');
    }

    /**
     * Get active company from cache
     */
    public function activeCompany()
    {
        // Let's cache the user's active company so we can retrieve it on the fly.
        $companyId = Cache::rememberForever("user:{$this->id}:activeCompany", function() {
            $company = $this->companies->first();

            if ( $company ) {
                return $company->id;
            }

            return false;
        });

        // if there isn't any companies, return false
        if ( false == $companyId ) {
            Cache::forget("user:{$this->id}:activeCompany");
            return false;
        }

        return Company::find( $companyId );
    }

    /**
     * User has settings
     */
    public function settings()
    {
        return $this->hasMany('App\Setting');
    }

    /**
     * Get specific setting
     */
    public function getSetting( $name )
    {
        return $this->settings()->where( 'name', $name )->first();
    }
}
