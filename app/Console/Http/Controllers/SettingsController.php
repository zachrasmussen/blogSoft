<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Company;
use App\Website;
use App\User;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index( Request $request )
    {
        return view('settings.index', ['user' => $request->user()]);
    }

    public function store( Request $request )
    {
        $request->validate([
            'name' => 'required'
        ]);

        if($request->input('user_id') > 0)
        {
            $user_id = $request->input('user_id');
            $user = User::find($user_id);
        }else{
            $user = $request->user();
        }


        $user->name = $request->input('name');

        if ( $request->filled('password') && $request->filled('password_confirmation') ) {
            if ( $request->input('password') !== $request->input('password_confirmation') ) {
                return back()->withErrors(['danger'=>'Passwords must match.']);
            }

            $user->password = bcrypt( $request->input('password') );
        }

        $user->save();

        return back()
            ->with('success','User Updated successfully.');
    }


    /**
     * Initial setup
     */
    public function setup( Request $request )
    {
        $page = ( $request->has('page') )
            ? $request->input('page')
            : 1;

        $company    = $request->user()->activeCompany();
        $google     = ($company) ? $company->getClient('analytics') : false;
        $facebook   = ($company) ? $company->getClient('facebook_page') : false;
        $instagram  = ($company) ? $company->getClient('instagram') : false;
        $twitter    = ($company) ? $company->getClient('twitter') : false;
        $pinterest  = ($company) ? $company->getClient('pinterest') : false;
        $youtube    = ($company) ? $company->getClient('youtube') : false;
        $freshbooks = ($company) ? $company->getClient('freshbooks') : false;

        return view( "setup.index", [
            'company'    => $company,
            'google'     => $google,
            
            'facebook'   => $facebook,
            'instagram'  => $instagram,
            'twitter'    => $twitter,
            'pinterest'  => $pinterest,
            'youtube'    => $youtube,

            'freshbooks' => $freshbooks,
        ] );
    }

    /**
     * Create company
     */
    public function newCompany( Request $request )
    {
        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
        ]);

        $company = new Company;
        $company->name = $request->input('name');
        $company->save();

        if ( $company->users->isEmpty() ) {
            $request->user()->companies()->save( $company, ['role' => 'admin'] );
        }

        // Store url as website data (for now)
        $url = ( substr( $request->input( 'url' ), 0, 4 ) === 'http' )
            ? $request->input( 'url' )
            : 'http://' . $request->input( 'url' );

        $website = new Website;
        $website->name = $request->input('name');
        $website->url  = $url;

        $company->websites()->save( $website );

        return response()->json([
            'company' => $company->id
        ]);
    }

    /**
     * Get OAuth redirect url for client setup
     */
    public function getRedirect( Request $request )
    {
        $request->validate([
            'client' => 'required'
        ]);

        return response()->json([
            'redirect' => route( $request->input('client') . '.redirect' )
        ]);
    }
}
