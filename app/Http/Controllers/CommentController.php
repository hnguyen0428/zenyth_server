<?php

namespace App\Http\Controllers;

use App\Exceptions\Exceptions;
use App\Exceptions\ResponseHandler as Response;
use App\Repositories\CommentRepository;
use App\Repositories\PinpostRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CommentController
 * @package App\Http\Controllers
 */
class CommentController extends Controller
{
    /**
     * @var PinpostRepository
     */
    private $pinpostRepo;
    /**
     * @var CommentRepository
     */
    private $commentRepo;

    function __construct(PinpostRepository $pinpostRepo,
                         CommentRepository $commentRepo)
    {
        $this->pinpostRepo = $pinpostRepo;
        $this->commentRepo = $commentRepo;
    }

    /**
     * Create a comment
     * @param Request $request, post request
     * @param $commentable_id
     * @return JsonResponse
     */
    public function create(Request $request, $commentable_id)
    {
        $user = $request->get('user');
        $userId = $user->id;
        $text = $request->get('text');
        $commentableType = $this->getCommentableType($request);

        // Check if the commentable object exists
        $this->commentableExists($commentableType, $commentable_id);

        // Data passed in to create a comment
        $data = [
            'user_id' => $userId,
            'commentable_type' => $commentableType,
            'text' => $text,
            'commentable_id' => (int)$commentable_id
        ];

        $comment = $this->commentRepo->create($data);
        $comment->makeHidden(['likes_count', 'replies_count']);

        return Response::dataResponse(true, ['comment' => $comment]);

    }

    /**
     * Return information on comment
     * @param Request $request
     * @param $comment_id
     * @return JsonResponse
     */
    public function read(Request $request, $comment_id)
    {
        if($request->has('fields')) {
            // Specifies fields to return
            $fields = $request->input('fields');
            $fields = explode(',', $fields);
            $comment = $this->commentRepo->read($comment_id, $fields);
        }
        else
            $comment = $this->commentRepo->read($comment_id);

        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        $comment->makeHidden('replies_count');
        $comment->addVisible('replies');
        return Response::dataResponse(true, ['comment' => $comment]);
    }

    /**
     * Get all image objects of this comment
     * @param Request $request
     * @param $comment_id
     * @return JsonResponse
     */
    public function readImages(Request $request, $comment_id)
    {
        $comment = $this->commentRepo->read($comment_id);
        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        $images = $comment->images;

        return Response::dataResponse(true, [
            'comment' => [
                'images' => $images
            ]
        ]);
    }

    /**
     * Edit comment
     * @param Request $request, post request
     *        rules: requires comment that is not empty
     * @param $comment_id
     * @return JsonResponse
     */
    public function update(Request $request, $comment_id)
    {
        $comment = $this->commentRepo->read($comment_id);
        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        // Validate comment owner
        $commentOwnerId = $comment->user_id;
        $userId = $request->get('user')->id;
        if ($userId != $commentOwnerId)
            Exceptions::invalidTokenException(sprintf(NOT_USERS_OBJECT,
                COMMENT));

        $request->except(['user_id']);
        $this->commentRepo->update($request, $comment);

        return Response::dataResponse(true, ['comment' => $comment]);
    }

    /**
     * Delete a comment, only available if comment belongs to logged in user
     * @param Request $request, delete request
     * @param $comment_id
     * @return JsonResponse
     */
    public function delete(Request $request, $comment_id)
    {
        $comment = $this->commentRepo->read($comment_id);
        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        // Validate comment owner
        $commentOwnerId = $comment->user_id;
        $userId = $request->get('user')->id;
        if ($userId != $commentOwnerId)
            Exceptions::invalidTokenException(sprintf(NOT_USERS_OBJECT,
                COMMENT));

        $this->commentRepo->delete($comment);

        return Response::successResponse(sprintf(DELETE_SUCCESS, COMMENT));
    }

    /**
     * Fetch all likes of this comment
     * @param Request $request
     * @param $comment_id
     * @return JsonResponse
     */
    public function fetchLikes(Request $request, $comment_id)
    {
        $comment = $this->commentRepo->read($comment_id);
        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        if($request->has('fields')) {
            $fields = $request->input('fields');
            $fields = explode(',', $fields);
        } else
            $fields = ['*'];

        return Response::dataResponse(true, [
            'likes' => $comment->likes()->get($fields)
        ]);
    }

    /**
     * Fetch all replies of this comment
     * @param Request $request
     * @param $comment_id
     * @return JsonResponse
     */
    public function fetchReplies(Request $request, $comment_id)
    {
        $comment = $this->commentRepo->read($comment_id);
        if ($comment == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, COMMENT));

        if($request->has('fields')) {
            $fields = $request->input('fields');
            $fields = explode(',', $fields);
        } else
            $fields = ['*'];

        return Response::dataResponse(true, [
            'replies' => $comment->replies()->get($fields)
        ]);
    }

    /* The functions below are used to determine what commentable type the
    comment is being placed on */

    /**
     * Get the type of comment
     * @param Request $request
     * @return null|string
     */
    public function getCommentableType(Request $request)
    {
        if($request->is('api/pinpost/comment/*'))
            return 'App\Pinpost';

        Exceptions::invalidRequestException();
    }

    /**
     * Check if the commentable object exists
     * @param $commentableType
     * @param $commentableId
     * @return bool
     */
    public function commentableExists($commentableType, $commentableId)
    {
        if($commentableType == 'App\Pinpost')
            if($this->pinpostRepo->read($commentableId))
                return true;

        $type = substr($commentableType, 4);
        Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, $type));
    }

}
