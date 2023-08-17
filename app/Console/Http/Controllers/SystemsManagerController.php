<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Event;
use App\System;
use App\SystemComponent;
use App\SystemNotes;

class SystemsManagerController extends Controller
{
    /**
     * Require Auth
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
    }

     /**
     * Display Systems Manager
     *
     * @return \Illuminate\Http\Response
     */
    public function index( $id = null, Request $request )
    {
        $user    = $request->user();
        $company = $user->activeCompany();

        // Select items from DB
        $systems = $company->systems;
        $notes = $company->systemNotes;

        $active = ( $id )
            ? $systems->firstWhere( 'id', $id )
            : false;

        if ( $active ) {
            $components = ( $request->input( 'type' ) )
                ? $active->components()->where('type_id', $request->input('type'))->orderBy('num')->get()
                : $active->components()->orderBy('num')->get();
        } else {
            $components = collect([]);
            
            foreach ( $systems as $system ) {
                $items = ( $request->input( 'type' ) )
                    ? $system->components()->where('type_id', $request->input('type'))->orderBy('num')->get()
                    : $system->components()->orderBy('num')->get();

                if ( $items->isNotEmpty() ) {
                    $components = $components->merge( $items );
                }
            }
        }

        return view(
            'systems-manager.index',
            [
                'active'     => $active,
                'company'    => $company,
                'systems'    => $systems,
                'components' => $components,
                'filter'     => $request->input('type'),
                'notes'      => $notes
            ]
        );
    }

    /**
     * Store Note (Assigned to a user)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeNote( Request $request )
    {
        $company = $request->user()->activeCompany();

        $note = ( $request->input('id') ) ? SystemNotes::find( $request->input('id') ) : new SystemNotes;
        $note->company_id = $company->id;
        $note->content = trim( $request->input('content') );
        $note->save();

        return back();
    }

    /**
     * Store Group
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeSystem( Request $request )
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name'       => 'required',
            'prefix'     => 'required'
        ]);

        $count = System::where( 'company_id', $request->input('company_id') )->count();

        $system = ( $request->input('id') ) ? System::find( $request->input('id') ) : new System;

        $system->name       = $request->input('name');
        $system->prefix     = $request->input('prefix');
        $system->order      = $count;
        $system->company_id = $request->input('company_id');
        $system->lock       = false; // Only predefined are locked (Not editable aside from order)

        $system->save();

        return redirect()->route('systems-manager', $system->id);
    }

    /**
     * Store System (Assigned to a group)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeComponent( Request $request )
    {
        $request->validate([
            'system' => 'required|exists:systems,id',
            'name'   => 'required',
            'url'    => 'required',
            'num'    => 'nullable',
            'type'   => 'nullable'
        ]);

        $num = ( $request->input('num') )
            ? $request->input('num')
            : System::find( $request->input('system') )->components->count() + 1;

        $typeId = ( $request->input('type') )
            ? $request->input('type')
            : 0;

        $url = ( substr( $request->input( 'url' ), 0, 4 ) === 'http' )
            ? $request->input( 'url' )
            : 'http://' . $request->input( 'url' );

        $system             = ( $request->input('id') ) ? SystemComponent::find( $request->input('id') ) : new SystemComponent;
        $system->system_id  = $request->input('system');
        $system->name       = $request->input('name');
        $system->num        = $num;
        $system->url        = $url;
        $system->type_id    = $typeId;
        $system->save();

        return back();
    }

    /**
     * Update Group 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSystemsOrder( Request $request )
    {
        $request->validate([
            'systems' => 'required|array'
        ]);

        foreach ( $request->input( 'systems' ) as $input ) {
            $system = System::findOrFail( $input['id'] );

            $system->update(['order' => $input['order']]);
        }
        
        return response()->json([
            'status' => 'success',
        ]);
    }

    public function deleteComponent( Request $request )
    {
        $request->validate([
            'id' => 'required|exists:system_components,id'
        ]);

        SystemComponent::destroy( $request->input( 'id' ) );

        return back();
    }

    public function deleteSystem( Request $request )
    {
        $request->validate([
            'id' => 'required|exists:systems,id'
        ]);

        System::destroy( $request->input( 'id' ) );

        return back();
    }
}
