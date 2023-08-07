<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    public $timestamps = false;
    
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
    public static function types()
    {
        return [
            'facebook'    => ['facebook.com'],
            'linkedin'    => ['linkedin.com'],
            'wordpress'   => ['wordpress.com'],
            'dropbox'     => ['dropbox.com'],
            'pinterest'   => ['pinterest.com'],
            'google-plus' => ['plus.google.com'],
            'instagram'   => ['instagram.com'],
            'youtube'     => ['youtube.com'],
            'twitter'     => ['twitter.com']
        ];
    }

    public function displayType()
    {
        switch ( $this->type ) {
            case 'facebook' :
                return 'Facebook';

            case 'linkedin' :
                return 'LinkedIn';

            case 'wordpress' :
                return 'Wordpress';

            case 'dropbox' :
                return 'Dropbox';

            case 'pinterest' :
                return 'Pinterest';

            case 'google-plus' :
                return 'Google Plus';

            case 'instagram' :
                return 'Instagram';

            case 'youtube' :
                return 'Youtube';

            case 'twitter' :
                return 'Twitter';
                
            case 'other' :
            default :
                return 'Other';
        }
    }
}
