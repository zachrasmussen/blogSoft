<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Clients\Facebook;
use App\Clients\Twitter;
use App\Clients\Instagram;
use App\Clients\Pinterest;
use App\Clients\YouTube;

use App\Client;

use App\Exceptions\ClientAuthenticationException;
use App\Exceptions\ClientNotFoundException;

class PartnerLink extends Model
{
    /**
     * Allow for fields to be mass-fillable
     */
    protected $fillable = [
        'partner_id', 'name', 'url'
    ];

    /**
     * Don't include timestamps
     */
    public $timestamps = false;

    /**
     * Get counts for link
     */
    public function getCounts() {
        try {
            $user = \Auth::user();
            $company = $user->activeCompany();

            switch ( $this->type ) {
                case 'facebook' :
                    $client = $company->getClient('facebook_page');
                    $facebook = new Facebook( $client );

                    return $facebook->getPostCounts( $this->post_id );

                case 'twitter' :
                    $client = $company->getClient('twitter');

                    if($client == null){
                        $client = Client::where(['type'=>'twitter'])->orderBy('id', 'DESC')->first();
                    }
                    
                    $twitter = new Twitter( $client );

                    return $twitter->getPostCounts( $this->post_id );

                case 'instagram' :
                    $client = $company->getClient('instagram');

                    /*if($client == null){
                        $client = Client::where(['type'=>'instagram'])->orderBy('id', 'DESC')->first();
                    }*/

                    $instagram = new Instagram( $client );

                    return $instagram->getPostCounts( $this->url );

                case 'pinterest' :
                    $client = $company->getClient('pinterest');

                    if($client == null){
                        $client = Client::where(['type'=>'pinterest'])->orderBy('id', 'DESC')->first();
                    }

                    $pinterest = new Pinterest( $client );

                    return $pinterest->getPostCounts( $this->post_id );

                case 'youtube' :
                    $client = $company->getClient('youtube');

                    if($client == null){
                        $client = Client::where(['type'=>'youtube'])->orderBy('id', 'DESC')->first();
                    }
                    $youtube = new YouTube( $client );

                    return $youtube->getPostCounts( $this->post_id );
            }

            return false;

        } catch ( ClientAuthenticationException $e ) {
            return false;
        } catch ( ClientNotFoundException $e ) {
            return false;
        } catch ( \Exception $e ) {
            // if we get any weird errors, let's show them for now
            // TODO: remove after beta testing
            echo '<div class="alert alert-danger">';
            echo 'Error getting data from post:<br>';
            echo $e->getMessage();
            echo '</div>';
            return false;
        }
    }

    public function checkCounts($type) {
        try {
            $user = \Auth::user();
            $company = $user->activeCompany();

            switch ($type) {
                case 'facebook' :
                    $client = $company->getClient('facebook_page');

                    if($client == null){
                        return 'no_account';
                    }

                    $facebook = new Facebook( $client );

                    return $facebook->getPostCounts( $this->post_id );

                case 'twitter' :
                    $client = $company->getClient('twitter');

                    if($client == null){
                        $client = Client::where(['type'=>'twitter'])->orderBy('id', 'DESC')->first();
                    }

                    if($client == null){
                        return 'no_account';
                    }

                    $twitter = new Twitter( $client );

                    return $twitter->checkTweet( $this->post_id );

                case 'instagram' :
                    $client = $company->getClient('instagram');

                    if($client == null){
                        return 'no_account';
                    }

                    $instagram = new Instagram( $client );

                    return $instagram->getPostCounts( $this->url );

                case 'pinterest' :
                    $client = $company->getClient('pinterest');

                    if($client == null){
                        $client = Client::where(['type'=>'pinterest'])->orderBy('id', 'DESC')->first();
                    }

                    if($client == null){
                        return 'no_account';
                    }

                    $pinterest = new Pinterest( $client );

                    return $pinterest->getPost( $this->post_id );

                case 'youtube' :
                    $client = $company->getClient('youtube');
                    if($client == null){
                        $client = Client::where(['type'=>'youtube'])->orderBy('id', 'DESC')->first();
                    }
                    if($client == null){
                        return 'no_account';
                    }
                    $youtube = new YouTube( $client );

                    return $youtube->getPostCounts( $this->post_id );
            }

            return false;

        } catch ( ClientAuthenticationException $e ) {
            return false;
        } catch ( ClientNotFoundException $e ) {
            return false;
        } catch ( \Exception $e ) {
            // if we get any weird errors, let's show them for now
            // TODO: remove after beta testing
            echo '<div class="alert alert-danger">';
            echo 'Error getting data from post:<br>';
            echo $e->getMessage();
            echo '</div>';
            return false;
        }
    }
}
