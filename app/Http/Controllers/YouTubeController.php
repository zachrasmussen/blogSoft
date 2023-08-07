<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_Exception;

use App\User;
use App\Client;
use App\ClientData;
use App\SocialCount;

use App\Clients\YouTube;
use Carbon\Carbon;

use App\Exceptions\ClientNotFoundException;
use App\Exceptions\ClientAuthenticationException;

class YouTubeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['refreshToken','getChannelSubscribers']);
    }

    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        if ( !$company ) {
            return redirect()->route('company');
        }

        $client = $company->getClient('youtube');
        if ( !$client ) {
            return view('settings.youtube');
        }

        $channel = $client->getData('channel');

        try {
            $youtube = new YouTube( $client );

            $channels = $youtube->getChannels();
        }
        catch ( ClientNotFoundException $e ) {
            return view('settings.youtube');
        }
        catch ( ClientAuthenticationException $e ) {
            return view('settings.youtube')
                ->withErrors(['danger'=>'Cannot connect to YouTube, please refresh your authorization with Google.']);
        }

        return view('settings.youtube', ['channels' => $channels, 'currentChannel' => (string) $channel]);
    }

    public function storeChannel( Request $request )
    {
        $request->validate([
            'channel' => 'required'
        ]);

        $company = $request->user()->activeCompany();
        $client = $company->getClient('youtube');

        ClientData::updateOrCreate(
            ['client_id' => $client->id, 'name' => 'channel'],
            ['value' => $request->input('channel')]
        );

        return redirect()->route('social-media')->with('success', 'Success! We\'re tracking your YouTube now, come back in a few days to see your activity!');
    }

    /**
     * Redirect to authorize with Google
     */
    public function redirect()
    {
        $google = new Google_Client;
        $google->setClientId( config( 'services.youtube.client_id' ) );
        $google->setClientSecret( config( 'services.youtube.client_secret' ) );
        $google->setRedirectUri( config( 'services.youtube.redirect' ) );
        $google->addScope('https://www.googleapis.com/auth/youtube.readonly');
        $google->setAccessType('offline');
        $google->setApprovalPrompt('force');
        $google->setRedirectUri( route( 'youtube.getToken' ) );

        return redirect()->away( $google->createAuthUrl() );
    }

    /**
     * Get token from code
     */
    public function getToken( Request $request )
    {
        $company = $request->user()->activeCompany();

        $google = new Google_Client;
        $google->setClientId( config( 'services.youtube.client_id' ) );
        $google->setClientSecret( config( 'services.youtube.client_secret' ) );
        $google->setRedirectUri( config( 'services.youtube.redirect' ) );
        $google->addScope('https://www.googleapis.com/auth/youtube.readonly');
        $google->setApprovalPrompt("force");
        $google->setAccessType('offline');

        if ( $request->has('code') ) {
            $token = $google->fetchAccessTokenWithAuthCode( $request->input('code') );

            $client = $company->getClient('youtube');

            if ( null == $client ) {
                $client = new Client;
            }
            if(!empty($token) && isset($token['access_token'])){
                $client->company_id    = $company->id;
                $client->type          = 'youtube';
                $client->username      = $company->name;
                $client->access_token  = $token['access_token'];
                $client->expiry        = $token['created'] + $token['expires_in'];

                if ( isset( $token['refresh_token'] ) ) {
                    $client->refresh_token = $token['refresh_token'];
                }

                $client->save();

                $data = ClientData::firstOrNew(
                    ['client_id' => $client->id, 'name' => 'token'],
                    ['value' => json_encode( $token )]
                );

                $data->save();
            }

        }

        if ( $request->has('error') ) {
            return redirect()->route('youtube')->withErrors('Couldn\'t connect to your Google Account. Please try again.');
        }

        return redirect()->route('youtube');
    }

    /** Youtube Refresh token Cron */
    public function refreshToken(Request $request){
        $users = User::where(['status'=>'active'])->get();

        if($users != null && sizeof($users) > 0){
            foreach($users as $user){
                $company = $user->activeCompany();
                $client = $company->getClient('youtube');
                if($client != null){

                    $timeCreated = ($client['expiry'])-3600;
                    $t=time();
                    $timediff=$t-$timeCreated;

                    $refreshToken = $client['refresh_token'];
                    if($refreshToken != null && ($timediff>3600)){
                        $google = new Google_Client;
                        $google->setClientId( config( 'services.youtube.client_id' ) );
                        $google->setClientSecret( config( 'services.youtube.client_secret' ) );
                        //$google->setRedirectUri( config( 'services.youtube.redirect' ) );
                        $google->setScopes('https://www.googleapis.com/auth/youtube.readonly');
                        $google->setAccessType('offline');
                        $google->refreshToken($refreshToken);
                        $token = $google->getAccessToken();
                        if(!empty($token) && isset($token['access_token'])){
                            $client->company_id    = $company->id;
                            $client->type          = 'youtube';
                            $client->username      = $company->name;
                            $client->access_token  = $token['access_token'];
                            $client->expiry        = $token['created'] + $token['expires_in'];

                            if ( isset( $token['refresh_token'] ) ) {
                                $client->refresh_token = $token['refresh_token'];
                            }

                            $client->save();

                            $data = ClientData::firstOrNew(
                                ['client_id' => $client->id, 'name' => 'token'],
                                ['value' => json_encode( $token )]
                            );

                            if($data->save()){
                                echo $user->id."----Updated<br>";
                            }
                        }
                    }else{
                        echo $user->id."----".$timediff."<br>";
                    }


                }
            }
        }

    }

    /** Get All Client's Subscribers **/
    public function getChannelSubscribers(){
        $clients = Client::where(['type'=>'youtube'])->get();
        $save = false;
        if($clients != null && sizeof($clients) > 0){
            foreach($clients as $client){
                if($client != null){
                    $youtube = new YouTube( $client );
                   $channelData = $client->getData('channel');
                   if(isset($channelData) && !empty($channelData->value)){
                       $channelID = $channelData->value;
                       $stats = $youtube->getChannelSubscribers($channelID);
                       $stats = (int) $stats;
                       if($stats > 0){
                           $socialCount = SocialCount::where(['client_id'=>$channelData->client_id])->whereDate('date', Carbon::today())->get()->toArray();

                           if(empty($socialCount)) {
                               $socialCount = new SocialCount;
                               $socialCount->client_id = $channelData->client_id;
                               $socialCount->count = $stats;
                               $socialCount->date = Carbon::now()->toDateString();
                               $save = $socialCount->save();
                           }


                           if($save || $save > 0){
                               echo $socialCount->client_id."--- Saved<br>";
                           }else{
                               //do nothing
                           }
                       }
                   }
                }
            }
        }else{
            echo "No record found!";
        }

    }
}
