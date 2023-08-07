<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;

class NotesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index( $id = null ) {
        $notes = Note::where('user_id', \Auth::id())->get();

        $activeNote = ( $id !== null )
            ? $notes->firstWhere( 'id', $id )
            : $notes->first();

        return view('notes.index', ['notes' => $notes, 'activeNote' => $activeNote]);
    }
    
    public function create()
    {
        $notes = Note::where('user_id', \Auth::id())->get();

        return view('notes.create', ['notes' => $notes]);
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'id'      => 'nullable|exists:notes,id',
            'name'    => 'required|max:60',
            'content' => 'required'
        ]);

        $note = ( $request->input('id') ) ? Note::findOrFail( $request->input('id') ) : new Note;

        $note->name    = $request->input('name');
        $note->content = $request->input('content');
        $note->user_id = $request->user()->id;

        $note->save();

        return redirect()->route('notes.single', ['id' => $note->id])->with('status', 'Note saved');
    }
    
    public function delete($id)
    {
        Note::destroy($id);

        return redirect()->route('notes')->with('status', 'Note deleted');
    }
}
