<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Comment;
use App\User;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'comment' => 'required|min:1',
            'entity_id' => 'required'
        ]);

        if($validator->fails()) {
            return $validator->errors()->all();
        }

        $comment = new Comment();

        $comment->entity_id = $request->input('entity_id');
        $comment->comment = $request->input('comment');

        $api_token = $request->header('Authorization');

        $comment->user_id = User::where('api_token', $api_token)->first()->id;
        $comment->save();

        return $comment;

    }

    public function read($comment_id)
    {

        $comment = Comment::find($comment_id);

        if ($comment == null) {
            return response(json_encode(['error' => 'not found']), 404);
        }

        return $comment;

    }

    public function update(Request $request, $comment_id)
    {

        $validator = Validator::make($request->all(), [
            'comment' => 'required|min:1'
        ]);

        if($validator->fails()) {
            return $validator->errors()->all();
        }

        $comment = Comment::find($comment_id);

        if ($comment == null) {
            return response(json_encode(['error' => 'not found']), 404);
        }

        if ($request->has('comment'))
            $comment->comment = $request->input('comment');

        $comment->update();

        return $comment;

    }

    public function delete($comment_id)
    {

        $comment = Comment::find($comment_id);

        if ($comment == null) {
            return response(json_encode(['error' => 'not found']), 404);
        }

        $comment->delete();

        return response(json_encode(['comment status' => 'deleted']), 200);

    }

}
