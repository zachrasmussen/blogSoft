<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Google_Client;
use Google_Service_Analytics;
use Google_Service_Exception;

use App\User;
use App\Client;
use App\ClientData;

use App\Clients\GoogleAnalytics;

use App\Exceptions\ClientNotFoundException;
use App\Exceptions\ClientAuthenticationException;

class GoogleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['refreshToken']);
    }

    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        if ( !$company ) {
            return redirect()->route('company');
        }

        $client = $company->getClient('analytics');
        if ( !$client ) {
            // if we don't have a client stored yet, show the button to authorize
            return view('settings.analytics');
        }

        $account = $client->getData('account');
        $profile = $client->getData('profile');

        $profiles = false;
        try {
            // if all the client data is stored, let's get the available options for getting the data
            $analytics = new GoogleAnalytics( $client );
            $profiles = $analytics->getOptions();
        }
        catch ( ClientNotFoundException $e ) {
            return view('settings.analytics');
        }
        catch ( \Google_Service_Exception $e ) {
            //return redirect()->route('analytics.change.account');
            return view('settings.analytics')
                ->withErrors(['danger'=>'Cannot connect to Google Analytics, please refresh your authorization with Google.']);
        }
        catch ( ClientAuthenticationException $e ) {
            return view('settings.analytics')
                ->withErrors(['danger'=>'Cannot connect to Google Analytics, please refresh your authorization with Google.']);
        }

        return view('settings.analytics', ['profiles' => $profiles, 'currentAccount' => (string) $account, 'currentProfile' => (string) $profile]);
    }

    public function storeProfile( Request $request )
    {
        $request->validate([
            'account' => 'required',
            'profile' => 'required'
        ]);

        $company = $request->user()->activeCompany();
        $client = $company->getClient('analytics');

        ClientData::updateOrCreate(
            ['client_id' => $client->id, 'name' => 'account'],
            ['value' => $request->input('account')]
        );

        ClientData::updateOrCreate(
            ['client_id' => $client->id, 'name' => 'profile'],
            ['value' => $request->input('profile')]
        );

        return redirect()->route('company')->with('success', 'Google Analytics set up successfully!');
    }

    /**
     * Redirect to authorize with Google
     */
    public function redirect()
    {
        $google = new Google_Client;
        $google->setClientId( config( 'services.google.client_id' ) );
        $google->setClientSecret( config( 'services.google.client_secret' ) );
        $google->setRedirectUri( config( 'services.google.redirect' ) );
        $google->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
        $google->setAccessType('offline');
        $google->setApprovalPrompt('force');
        $google->setRedirectUri( route( 'analytics.getToken' ) );

        return redirect()->away( $google->createAuthUrl() );
    }

    /**
     * Get token from code
     */
    public function getToken( Request $request )
    {
        $company = $request->user()->activeCompany();

        $google = new Google_Client;
        $google->setClientId( config( 'services.google.client_id' ) );
        $google->setClientSecret( config( 'services.google.client_secret' ) );
        $google->setRedirectUri( config( 'services.google.redirect' ) );
        $google->addScope( Google_Service_Analytics::ANALYTICS_READONLY );
        $google->setApprovalPrompt("force");
        $google->setAccessType('offline');

        if ( $request->has('code') ) {
            $token = $google->fetchAccessTokenWithAuthCode( $request->input('code') );

            $client = $company->getClient('analytics');

            if ( null == $client ) {
                $client = new Client;
            }

            $client->company_id    = $company->id;
            $client->type          = 'analytics';
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

        if ( $request->has('error') ) {
            return redirect()->route('analytics')->withErrors('Couldn\'t connect to your Google Account. Please try again.');
        }

        return redirect()->route('analytics');
    }

    /**Google Analytics Refresh Token*/
    public function refreshToken(Request $request){
        $users = User::where(['status'=>'active'])->get();

        if($users != null && sizeof($users) > 0){
            foreach($users as $user){
                $company = $user->activeCompany();
                $client = $company->getClient('analytics');
                if($client != null){

                    $timeCreated = ($client['expiry'])-3600;
                    $t=time();
                    $timediff=$t-$timeCreated;

                    $refreshToken = $client['refresh_token'];
                    if($refreshToken != null && ($timediff>3600)){
                        $google = new Google_Client;
                        $google->setClientId( config( 'services.google.client_id' ) );
                        $google->setClientSecret( config( 'services.google.client_secret' ) );
                        $google->setScopes(Google_Service_Analytics::ANALYTICS_READONLY);
                        $google->setAccessType('offline');
                        $google->refreshToken($refreshToken);
                        $token = $google->getAccessToken();
                        if(!empty($token) && isset($token['access_token'])){
                            $client->company_id    = $company->id;
                            $client->type          = 'analytics';
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

    /** Change Google Analytic Account **/
    public function changeGaAccount(Request $request){
        $company = $request->user()->activeCompany();
        $client = null;
        if ( null != $company ) {
            $client = $company->getClient('analytics');
        }

        if($client != null){
            $response = $client->delete();
            if($response){
                return view('settings.analytics');
            }
        }else{
            return redirect()->route('analytics')->withErrors('Please Connect Your Google Analytic Account First!');
        }
    }
}
