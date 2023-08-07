<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * oAuth2 Client Data Model
 */

class Client extends Model
{
    public function website()
    {
        return $this->belongsTo('App\Website');
    }

    /**
     * Get the label for this client
     */
    public function getLabel()
    {
        switch ( $this->type ) {
            case 'facebook_page' :
            case 'facebook_user' :
                return 'Facebook';

            case 'twitter' :
                return 'Twitter';

            case 'instagram' :
                return 'Instagram';

            case 'pinterest' :
                return 'Pinterest';

            case 'youtube' :
                return 'YouTube';
        }
    }

    /**
     * Get the label for this client
     */
    public function getColor()
    {
        switch ( $this->type ) {
            case 'facebook_page' :
            case 'facebook_user' :
                return '#3B5999';

            case 'twitter' :
                return '#08A0E9';

            case 'instagram' :
                return '#D53679';

            case 'pinterest' :
                return '#BD071D';

            case 'youtube' :
                return '#FE0000';
        }
    }

    /**
     * Has many data ()
     */
    public function data()
    {
        return $this->hasMany('App\ClientData');
    }

    /**
     * Get data by name
     */
    public function getData( $name )
    {
        return $this->data()->where( 'name', $name )->first();
    }

    /**
     * Has many SocialCounts
     */
    public function counts()
    {
        return $this->hasMany('App\SocialCount');
    }

    /**
     * Get count for specific date
     */
    public function getCount( $date )
    {
        return $this->counts()->where( 'date', $date )->first();
    }

    /**
     * Get counts between two dates
     */
    public function getCountsFor( $start, $end )
    {
        return $this->counts()
            ->whereBetween( 'date', [$start, $end] )->get()
            ->map(function($item) {
                return $item->count;
            });
    }

    public function analytic()
    {
        return $this->hasMany('App\Analytic');
    }
}
