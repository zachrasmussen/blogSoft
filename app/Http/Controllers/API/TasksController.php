<?php

namespace App\Http\Controllers\API;

use App\TaskBoard;
use App\Task;
use App\TaskLabel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Boards
     */
    public function storeBoard( Request $request )
    {
        $data = $request->validate([
            'id'    => 'nullable|numeric',
            'name'  => 'required|max:60',
            'color' => 'nullable|in:blue,purple,green,yellow,red,gray',
        ]);

        $board = ( $data->id ) ? TaskBoard::firstOrFail( $data->id ) : new TaskBoard;

        $board->name = $data->name;
        $board->color = ( $data->color ) ? $data->color : 'gray';

        $board->save();

        return response()->json([
            'status' => 'OK',
            'message' => 'Board created successfully'
        ]);
    }

    public function deleteBoard( Request $request )
    {
        $data = $request->validate([
            'id' => 'required|exists:task_boards,id'
        ]);

        TaskBoard::destroy( $data->id );
    }

    /**
     * Tasks
     */
    public function getTasks( Request $request )
    {
        return response()->json( Task::all() );
    }

    public function storeTask( Request $request )
    {
        $data = $request->validate([
            'id'   => 'nullable|numeric',
            'name' => 'required|max:60',
            'due'  => 'nullable|date',
            'list' => 'required|exists:task_lists,id',
        ]);

        $task = ( $data->id ) ? Task::firstOrFail( $data->id ) : new Task;
    }

    public function deleteTask( Request $request )
    {}

    /**
     * Lists
     */
    public function storeList( Request $request )
    {}

    public function deleteList( Request $request )
    {}

    /**
     * Labels
     */
    public function getLabels( $id )
    {
        $taskBoard = TaskBoard::findOrFail( $id );

        return response()->json([
            'status' => 'OK',
            'labels' => $taskBoard->labels()
        ]);
    }

    public function storeLabel( Request $request )
    {
        $data = $request->validate([
            'id'    => 'nullable|numeric',
            'name'  => 'required|max:60',
            'color' => 'required|in:blue,purple,green,yellow,red,gray',
            'board' => 'required|exists:task_boards,id'
        ]);

        $label = ( $request->input('id') )
            ? TaskLabel::firstOrFail( $data->id )
            : new TaskLabel;

        $label->name          = $request->input('name');
        $label->color         = $request->input('color');
        $label->task_board_id = $request->input('board');

        $label->save();

        return response()->json([
            'status' => 'OK',
            'message' => "Label \"$label->name\" saved"
        ]);
    }

    public function deleteLabel( Request $request )
    {}
}
