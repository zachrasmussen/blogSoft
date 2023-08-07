<?php

namespace App\Http\Controllers;

use App\Meeting;
use Illuminate\Http\Request;

class MeetingsController extends Controller
{
    /**
     * Require Auth
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display Meetings
     *
     * @return \Illuminate\Http\Response
     */
    public function index( $id = null ) {
        $meetings = Meeting::where('user_id', \Auth::id())->get();

        $activeMeeting = ( $id !== null )
            ? $meetings->firstWhere( 'id', $id )
            : $meetings->first();


        return view('meetings.index', ['meetings' => $meetings, 'activeMeeting' => $activeMeeting]);
    }

    public function create()
    {
        $meetings = Meeting::where('user_id', \Auth::id())->get();

        return view('meetings.create', ['meetings' => $meetings]);
    }

    public function store(Request $request)
    {
        // // Meetings validation
        // $data = $request->validate([
        //     'title'    => 'required|max:30',
        //     'created_at'    => 'required|max:30',
        //     'updated_at'    => 'required|max:30',
        //     'start_time'    => 'required|max:30',
        //     'end_time'    => 'required|max:30',
        //     'date'    => 'required|max:30',
        //     'attendees'    => 'required|max:60',
        //     'agenda'    => 'required|max:60',
        //     'action'    => 'required|max:60',
        //     'notes'    => 'required|max:120',
        // ]);

        $meeting = ( $request->input('id') ) ? Note::findOrFail( $request->input('id') ) : new Meeting;

        $meeting->title    = $request->input('title');
        $meeting->created_at    = $request->input('created_at');
        $meeting->updated_at    = $request->input('updated_at');
        $meeting->start_time    = $request->input('start_time');
        $meeting->end_time    = $request->input('end_time');
        $meeting->date    = $request->input('date');
        $meeting->attendees    = $request->input('attendees');
        $meeting->agenda    = $request->input('agenda');
        $meeting->action    = $request->input('action');
        $meeting->notes    = $request->input('notes');
        $meeting->user_id = $request->user()->id;

        $meeting->save();

        return redirect()->route('meetings.single', ['id' => $meeting->id]);
    }
}
