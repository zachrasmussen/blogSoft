<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use Socialite;
use App\Setting;
use App\Client;
use App\ClientData;

class FacebookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['refreshToken']);
    }
    
    /**
     * Render facebook settings
     */
    public function index(  Request $request  )
    {
        $company = $request->user()->activeCompany();
        $client = $company->clients()->where('type', 'facebook_page')->first();

        return view('settings.facebook', ['client' => $client]);
    }

    /**
     * Redirect to Facebook
     */
    public function redirect()
    {
        return Socialite::driver('facebook')
            ->fields(['first_name', 'last_name', 'email'])
            ->scopes(['email', 'manage_pages', 'read_insights', 'pages_show_list'])
            ->redirect();
    }

    /**
     * Get facebook user data
     */
    public function getToken( Request $request )
    {
        try {
            $fb = Socialite::driver('facebook')
                ->fields(['first_name', 'last_name', 'email', 'accounts'])
                ->user();

            $user = $request->user();
            $company = $user->activeCompany();

            $client = $company->getClient( 'facebook_user' );

            if ( null == $client ) {
                $client = new Client;
            }

            $client->company_id    = $company->id;
            $client->type          = 'facebook_user';
            $client->username      = $fb->id;
            $client->access_token  = $fb->token;
            $client->refresh_token = $fb->refreshToken;
            $client->expiry        = $fb->expiresIn;

            $client->save();

            if ( !empty( $fb->user['accounts'] ) ) {
                return redirect()->route('facebook')->with('pages', $fb->user['accounts']['data']);
            }
        } catch ( \Exception $e ) {
            return redirect()->route('facebook')->withErrors( $e->getMessage() );
        }
    }

    /**
     * Store page data
     */
    public function storeAccount( Request $request )
    {
        $pageData = explode( '.', $request->input('page') );
        $user     = $request->user();
        $company  = $user->activeCompany();

        $client = $company->getClient('facebook_page');

        if ( null == $client ) {
            $client = new Client;
        }
        
        $client->company_id    = $company->id;
        $client->type          = 'facebook_page';
        $client->username      = $pageData[0];
        $client->access_token  = $pageData[1];
        $client->refresh_token = null;
        $client->expiry        = null;

        $client->save();

        return redirect()->route('social-media')->with('success', 'Success! We\'re tracking your Facebook Page now, come back in a few days to see your activity!');
    }

    /**Refresh Access Token**/
    public function refreshToken(Request $request){
        $users = User::where(['status'=>'active'])->get();
        if($users != null && sizeof($users) > 0){
            foreach($users as $user){
                $company = $user->activeCompany();
                $clientId = config( 'services.facebook.app_id' );
                $clientSecret = config( 'services.facebook.app_secret' );

                $facebookUser = $company->getClient('facebook_user');
                if($facebookUser != null){
                    if(isset($facebookUser['access_token']) && $facebookUser['access_token'] != null){
                        $token = $facebookUser['access_token'];
                        $url = "https://graph.facebook.com/oauth/access_token?client_id=".$clientId."&client_secret=".$clientSecret."&grant_type=fb_exchange_token&fb_exchange_token=".$token."";
                        $client = new \GuzzleHttp\Client();
                        $jsonData = $client->request('GET', $url);
                        $jsonData = json_decode($jsonData->getBody());

                        if(!empty($jsonData) && isset($jsonData->access_token)){
                            $facebookUser->access_token = $jsonData->access_token;
                            if($facebookUser->save()){
                                echo $facebookUser->company_id."---".$facebookUser->type."----".$facebookUser->username."<br>";
                            }
                        }
                    }
                }

                $facebookPage = $company->getClient('facebook_page');
                if($facebookPage != null){
                    if(isset($facebookPage['access_token']) && $facebookPage['access_token'] != null){
                        $token = $facebookPage['access_token'];
                        $url = "https://graph.facebook.com/oauth/access_token?client_id=".$clientId."&client_secret=".$clientSecret."&grant_type=fb_exchange_token&fb_exchange_token=".$token."";
                        $client = new \GuzzleHttp\Client();
                        $jsonData = $client->request('GET', $url);
                        $jsonData = json_decode($jsonData->getBody());
                        if(!empty($jsonData) && isset($jsonData->access_token)){
                            $facebookPage->access_token = $jsonData->access_token;
                            if($facebookPage->save()){
                                echo $facebookPage->company_id."---".$facebookPage->type."----".$facebookPage->username."<br>";
                            }
                        }
                    }
                }
            }
        }
    }
}
