<?php

namespace App\Repositories;

class RelationshipRepository extends Repository
                            implements RelationshipRepositoryInterface
{
    function model()
    {
        return 'App\Relationship';
    }

    /**
     * Get all users that this user blocked
     * @param $userId
     * @return mixed
     */
    public function getAllBlockedUsers($userId)
    {
        $query = $this->model->select('users.*')
            ->join('users', 'users.id', '=', 'requestee')
            ->where('requester', '=', $userId)
            ->where('blocked', '=', true);

        $this->model = $query;
        return $this;
    }

    /**
     * Get all friends of this user
     * @param $userId
     * @return mixed
     */
    public function getAllFollowers($userId)
    {
        $queryOne = $this->model->select('users.*')
            ->join('users', 'users.id', '=', 'requester')
            ->where('requestee', '=', $userId)
            ->where('status', '=', true);

        $queryTwo = $this->model->select('users.*')
            ->join('users', 'users.id', '=', 'requestee')
            ->where('requester', '=', $userId)
            ->where('status', '=', true);;

        $query = $queryOne->union($queryTwo);
        $this->model = $query;
        return $this;
    }

    /**
     * Get all friend requests to this user
     * @param $userId
     * @return mixed
     */
    public function getAllFollowerRequests($userId)
    {
        $query = $this->model->select('users.*')
            ->join('users', 'users.id', '=', 'requester')
            ->where('requestee', '=', $userId)
            ->where('status', '=', false);

        $this->model = $query;
        return $this;
    }

    /**
     * All relationships that are blocked
     * @return mixed
     */
    public function isBlocked()
    {
        $query = $this->model->where('blocked', '=', true);
        $this->model = $query;
        return $this;
    }

    /**
     * Get relationship if it exists where requester is a follower of requestee
     * @param $userOneId
     * @param $userTwoId
     * @return mixed
     */
    public function getFollowRelationship($requesterId, $requesteeId)
    {
        $query = $this->model->where([
            ['requester', '=', $requesterId],
            ['requestee', '=', $requesteeId],
            ['status', '=', true]
        ]);

        $this->model = $query;
        return $this;
    }

    /**
     * Relationship between two users
     * @param $userOneId
     * @param $userTwoId
     * @return mixed
     */
    public function getFollowRequest($requesterId, $requesteeId)
    {
        $query = $this->model->where([
            ['requester', '=', $requesterId],
            ['requestee', '=', $requesteeId],
            ['status', '=', false]
        ]);

        $this->model = $query;
        return $this;
    }

    public function getRelationship($requesterId, $requesteeId)
    {
        $query = $this->model->where([
            ['requester', '=', $requesterId],
            ['requestee', '=', $requesteeId]
        ]);

        $this->model = $query;
        return $this;
    }

}