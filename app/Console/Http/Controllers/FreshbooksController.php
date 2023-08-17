<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Freshbooks;
use App\Setting;

class FreshbooksController extends Controller
{
    private $settingName = 'freshbooks_access_token';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $setting = Setting::where(['user_id' => Auth::id(), 'name' => $this->settingName])->first();

        $token = ( $setting )
            ? json_decode( $setting->value )
            : false;

        return view( 'settings.freshbooks', ['token' => $token] );
    }

    public function getToken( Request $request )
    {
        $freshbooks = new Freshbooks;

        if ( $request->has('code') ) {
            $freshbooks->authenticate( $request->input('code') );
            $token = $freshbooks->getAccessToken();

            if ( $token ) {
                return redirect()->route('freshbooks');
            }

            return redirect()->route('freshbooks')->with('error', 'There was an error connecting with your account, please try again later.');
        } else {
            return redirect()->away( $freshbooks->getAuthorizationUrl() );
        }
    }
}
