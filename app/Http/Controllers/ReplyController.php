<?php


namespace App\Http\Controllers;


use App\Exceptions\Exceptions;
use App\Exceptions\ResponseHandler as Response;
use App\Repositories\CommentRepository;
use App\Repositories\ReplyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    private $replyRepo;
    private $commentRepo;

    public function __construct(ReplyRepository $replyRepo,
                                CommentRepository $commentRepo)
    {
        $this->replyRepo = $replyRepo;
        $this->commentRepo = $commentRepo;
    }

    /**
     * Create a reply
     * @param Request $request, post request
     * @param @comment_id
     * @return JsonResponse
     */
    public function create(Request $request, $comment_id)
    {
        $user = $request->get('user');
        $text = $request['text'];

        // Check if the comment exists
        if(!$this->commentRepo->findBy('id', $comment_id))
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        // Data of reply
        $data = [
            'text' => $text,
            'user_id' => $user->id,
            'comment_id' => (int)$comment_id
        ];
        $reply = $this->replyRepo->create($data);
        $reply->makeHidden(['creator', 'likes_count']);
        return Response::dataResponse(true, ['reply' => $reply]);
    }

    /**
     * Return information on reply
     * @param Request $request
     * @param $reply_id
     * @return JsonResponse
     */
    public function read(Request $request, $reply_id)
    {
        if($request->has('fields')) {
            // Specifies fields to return
            $fields = $request->input('fields');
            $fields = explode(',', $fields);
            $reply = $this->replyRepo->read($reply_id, $fields);
        }
        else
            $reply = $this->replyRepo->read($reply_id);

        if ($reply == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, REPLY));

        return Response::dataResponse(true, ['reply' => $reply]);
    }

    /**
     * Get all image objects of this reply
     * @param Request $request
     * @param $reply_id
     * @return JsonResponse
     */
    public function readImages(Request $request, $reply_id)
    {
        $reply = $this->replyRepo->read($reply_id);
        if ($reply == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND,  REPLY));
        $images = $reply->images;

        return Response::dataResponse(true, [
            'reply' => [
                'images' => $images
            ]
        ]);
    }

    /**
     * Edit reply
     * @param Request $request, post request
     *        rules: requires reply that is not empty
     * @param $reply_id
     * @return JsonResponse
     */
    public function update(Request $request, $reply_id)
    {
        $reply = $this->replyRepo->read($reply_id);
        if ($reply == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND,  REPLY));

        // Validate if reply belongs to user
        $replyOwnerId = $reply->user_id;
        $userId = $request->get('user')->id;
        if ($userId != $replyOwnerId)
            Exceptions::invalidTokenException(sprintf(NOT_USERS_OBJECT,
                REPLY));

        $request->except(['user_id']);
        $this->replyRepo->update($request, $reply);

        return Response::dataResponse(true, ['reply' => $reply]);
    }

    /**
     * Delete a reply, only available if reply belongs to logged in user
     * @param Request $request, delete request
     * @param $reply_id
     * @return JsonResponse
     */
    public function delete(Request $request, $reply_id)
    {
        $reply = $this->replyRepo->read($reply_id);
        if ($reply == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND,  REPLY));

        // Validate if reply belongs to user
        $replyOwnerId = $reply->user_id;
        $userId = $request->get('user')->id;
        if ($userId != $replyOwnerId)
            Exceptions::invalidTokenException(sprintf(NOT_USERS_OBJECT,
                REPLY));

        $this->replyRepo->delete($reply);

        return Response::successResponse(sprintf(DELETE_SUCCESS, REPLY));
    }

    /**
     * Fetch all likes of this reply
     * @param Request $request
     * @param $reply_id
     * @return JsonResponse
     */
    public function fetchLikes(Request $request, $reply_id)
    {
        $reply = $this->replyRepo->read($reply_id);
        if ($reply == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND,  REPLY));
        if($request->has('fields')) {
            $fields = $request->input('fields');
            $fields = explode(',', $fields);
        } else
            $fields = ['*'];

        return Response::dataResponse(true, [
            'likes' => $reply->likes()->get($fields)
        ]);
    }

}