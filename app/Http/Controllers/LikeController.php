<?php

namespace App\Http\Controllers;

use App\Exceptions\Exceptions;
use App\Exceptions\ResponseHandler as Response;
use App\Repositories\CommentRepository;
use App\Repositories\LikeRepository;
use App\Repositories\PinpostRepository;
use App\Repositories\ReplyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class LikeController
 * @package App\Http\Controllers
 */
class LikeController extends Controller
{
    private $likeRepo;
    private $commentRepo;
    private $pinpostRepo;
    private $replyRepo;

    function __construct(LikeRepository $likeRepo,
                        PinpostRepository $pinpostRepo,
                        CommentRepository $commentRepo,
                        ReplyRepository $replyRepo)
    {
        $this->likeRepo = $likeRepo;
        $this->commentRepo = $commentRepo;
        $this->pinpostRepo = $pinpostRepo;
        $this->replyRepo = $replyRepo;
    }

    /**
     * Create a Like
     * @param Request $request, post request
     *        rules: requires entity_id
     * @return JsonResponse
     */
    public function create(Request $request, $likeable_id)
    {
        $user = $request->get('user');
        $likeableType = $this->getLikeableType($request);

        // Check if likeable object exists
        $this->likeableExists($likeableType, $likeable_id);

        // Go through to see if this user has already liked this likeable
        // object
        $likes = $user->likes;
        foreach($likes as $like)
            if($like->likeable_id == $likeable_id
                && $like->likeable_type == $likeableType)
                Exceptions::invalidRequestException(ALREADY_LIKED_ENTITY);

        // Like data to be created
        $data = [
            'likeable_type' => $likeableType,
            'likeable_id' => (int)$likeable_id,
            'user_id' => $user->id
        ];
        $like = $this->likeRepo->create($data);

        return Response::dataResponse(true, ['like' => $like]);
    }

    /**
     * Return information of like object
     * @param Request $request
     * @param $like_id
     * @return JsonResponse
     */
    public function read(Request $request, $like_id)
    {
        $like = $this->likeRepo->read($like_id);
        if($like == null)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, LIKE));

        return Response::dataResponse(true, ['like' => $like]);
    }

    /**
     * Delete a like
     * @param Request $request , delete request
     * @param $entity_id , entity the like is on
     * @return JsonResponse
     */
    public function delete(Request $request, $like_id)
    {
        $like = $this->likeRepo->read($like_id);
        if (!$like)
            Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, LIKE));

        // Validate if like belongs to this user
        $likeOwnerId = $like->user_id;
        $userId = $request->get('user')->id;
        $type = $this->getLikeableType($request);

        if ($userId != $likeOwnerId)
            Exceptions::invalidTokenException(sprintf(NOT_USERS_OBJECT,
                $type));

        $this->likeRepo->delete($like);
        return Response::successResponse(sprintf(DELETE_SUCCESS, LIKE));
    }

    /* The functions below are to determine the likeable type for
    polymorphism */

    /**
     * Get likeable type
     * @param Request $request
     * @return null|string
     */
    public function getLikeableType(Request $request)
    {
        if($request->is('api/pinpost/like/*'))
            return 'App\Pinpost';
        else if($request->is('api/comment/like/*'))
            return 'App\Comment';
        else if($request->is('api/reply/like/*'))
            return 'App\Reply';

        Exceptions::invalidRequestException();
    }

    /**
     * Check if likeable object exists
     * @param $likeableType
     * @param $likeableId
     * @return bool
     */
    public function likeableExists($likeableType, $likeableId)
    {
        if($likeableType == 'App\Pinpost')
            if($this->pinpostRepo->read($likeableId))
                return true;

        if($likeableType == 'App\Comment')
            if($this->commentRepo->read($likeableId))
                return true;

        if($likeableType == 'App\Reply')
            if($this->replyRepo->read($likeableId))
                return true;

        $type = substr($likeableType, 4);
        Exceptions::notFoundException(sprintf(OBJECT_NOT_FOUND, $type));
    }

}
