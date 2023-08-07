<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Website;

class WebsitesController extends Controller
{
    /**
     * Require Auth
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all user's websites
     */
    public function index()
    {
        $user = Auth::user();
        $company = $user->companies->first();
        $websites = $company->websites;

        return view('websites.index', ['websites' => $websites]);
    }

    /**
     * Store website
     */
    public function storeWebsite( Request $request )
    {
        $data = $request->validate([
            'id'   => 'nullable|exists:websites,id',
            'name' => 'required|max:60',
            'url'  => 'required|url'
        ]);

        $website = ( $request->input('id') )
            ? Website::find( $request->input('id') )
            : new Website;

        $website->name = $request->input('name');
        $website->url  = $request->input('url');

        $website->save();

        // $website->users()->attach( Auth::id(), ['role' => 'admin'] );

        return back();
    }
}
