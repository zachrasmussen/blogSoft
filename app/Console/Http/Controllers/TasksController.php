<?php

namespace App\Http\Controllers;

use App\TaskBoard;
use App\TaskList;
use App\Task;
use App\TaskLabel;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function boards()
    {
        $user = \Auth::user();
        $userBoards = TaskBoard::where(['owner_id' => $user->id])->get();
        // $userBoards = TaskBoard::all(); // to get ALL from db
        $sharedBoards = [];

        return view( 'tasks.boards', ['userBoards' => $userBoards, 'sharedBoards' => $sharedBoards] );
    }

    public function tasks( Request $request, $boardID )
    {
        $board = TaskBoard::findOrFail( $boardID );
        $usersBoards = TaskBoard::all();

        return view( 'tasks.tasks', ['board' => $board, 'usersBoards' => $usersBoards, 'lists' => $board->lists] );
    }

    /**
     * Store actions
     */
    public function storeBoard( Request $request )
    {
        // name, color, owner_id
        $board = ( $request->input('id') )
            ? TaskBoard::firstOrFail( $request->input('id') )
            : new TaskBoard;

        $board->name = $request->input('name');
        $board->color = $request->input('color');
        //$board->user = \Auth::user();
        $board->owner_id = \Auth::id();

        $board->save();

        return redirect()->route( 'tasks', [$board] );
    }

    public function storeList( Request $request )
    {
        $list = ( $request->input('id') )
            ? TaskList::firstOrFail( $request->input('id') )
            : new TaskList;

        $list->name          = $request->input('name');
        $list->position      = $request->input('position', 0);
        $list->task_board_id = $request->input('task_board_id');

        $list->save();

        return back();
    }

    public function storeLabel( Request $request )
    {
        $label = ( $request->input( 'id' ) )
            ? TaskLabel::firstOrFail( $request->input('id') )
            : new TaskLabel;

        $label->name = $request->input('name');
        $label->color = $request->input('color');
        $label->task_board_id = $request->input('board');

        $label->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Label successfully added.',
            'label'   => $label
        ]);
    }

    public function storeTask( Request $request )
    {
        $task = ( $request->input('id') )
            ? Task::firstOrFail( $request->input('id') )
            : new Task;

        $task->name         = $request->input('name');
        $task->due          = $request->input('due');
        $task->position     = $request->input('position');
        $task->task_list_id = $request->input('task_list_id');

        $task->save();

        return back();
    }

    /**
     * Delete actions
     */
    public function deleteBoard( Request $request )
    {}

    public function deleteList( Request $request )
    {}

    public function deleteLabel( Request $request )
    {}

    public function deleteTask( Request $request )
    {}
}
