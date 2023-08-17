<?php

namespace App\Http\Controllers;

use App\QuickLink;
use Illuminate\Http\Request;

class QuickLinksController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index( Request $request )
    {
        // $links = $request->user()->quickLinks;
        $links = QuickLink::where('user_id', \Auth::id())->get();

        return view('quick-links.index', ['links' => $links]); 
    }

    public function store( Request $request )
    {
        $request->validate([
            'url'      => 'required|active_url',
            'id'       => 'nullable|exists:quick_links,id',
            'position' => 'nullable|integer'
        ]);

        $link = ( $request->input('id') ) ? QuickLink::find( $request->input('id') ) : new QuickLink;

        $link->url      = $request->input('url');
        // $link->type     = $this->getType( $request );
        // $link->website_id  = $request->user()->id; // Todo prompt for the website
        $link->type       = $request->input('title');
        $link->user_id    = $request->user()->id;
        $link->website_id = $request->user()->id;
        $link->position   = $request->input('position', 0);

        $link->save();

        return back();
    }

    public function delete( Request $request )
    {}

    public function bulk( Request $request )
    {
        return back();
    }

    private function getType( Request $request )
    {
        $types = QuickLink::types();

        foreach ( $types as $key => $urls ) {
            foreach ( $urls as $url ) {
                if ( strpos( $request->input('url'), $url ) != false ) {
                    return $key;
                }
            }
        }

        $urlProp = array("http://", "https://", "www.", '/');
        $result = str_replace($urlProp,"", $request->input('url'));
        return $result; //'other'
    }
}
