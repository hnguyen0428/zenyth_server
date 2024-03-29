<?php

namespace Tests\Feature;

use App\Entity;
use App\Pinpost;
use App\User;
use App\Like;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LikesAndCommentsTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLikeCreation()
    {

        $user = factory('App\User')->create();
        $pinpost = factory('App\Pinpost')->create();

        $this->assertEquals(0, $pinpost->likesCount());

        $this->json('POST', '/api/pinpost/like/' . $pinpost->id,
            [], ['Authorization' => 'bearer ' . $user->api_token]);

        $this->assertEquals(1, $pinpost->likesCount());

    }

    public function testLikeDeletion()
    {

        $like = factory('App\Like')->create();

        $pinpost = Pinpost::find($like->likeable_id);
        $numLikes = $pinpost->likesCount();

        $this->json('DELETE', '/api/like/' . $like->id, [], ['Authorization' => 'bearer ' . $like->user->api_token]);

        $this->assertEquals($numLikes-1, $pinpost->likesCount());

    }

    public function testCommentCreation()
    {

        $user = factory('App\User')->create();
        $pinpost = factory('App\Pinpost')->create();

        $this->assertEquals(0, $pinpost->commentsCount());

        $response = $this->json('POST', '/api/pinpost/comment/' .
            $pinpost->id, ['text' => 'test comment'],
            ['Authorization' => 'bearer ' . $user->api_token]);

        $response->assertStatus(200);
        $this->assertEquals(1, $pinpost->commentsCount());

    }

    public function testCommentRead()
    {
        $comment = factory('App\Comment')->create(['text' => 'test comment']);

        $response = $this->json('GET', '/api/comment/read/' . $comment->id);

        $this->assertEquals(1, Pinpost::find($comment->commentable_id)
            ->commentsCount());

        $response->assertJson([
            'success' => true,
            'data' => [
                'comment' => [
                    'text' => 'test comment',
                    'id' => $comment->id,
                    'user_id' => $comment->user_id
                ]
            ]
        ]);
    }

    public function testCommentUpdate()
    {

        $comment = factory('App\Comment')->create();

        $text = $comment->comment;

        $response = $this->json('PATCH', '/api/comment/' . $comment->id, ['text' => 'NewText!!'],
            ['Authorization' => 'bearer ' . User::find($comment->user_id)
                    ->api_token]);

        $response->assertStatus(200);

        $comment_array = $response->json();
        $this->assertNotEquals($text, $comment_array['data']['comment']['text']);
        $this->assertEquals('NewText!!', $comment_array['data']['comment']['text']);
        $this->assertDatabaseHas('comments', ['text' => 'NewText!!']);

    }

    public function testCommentDelete()
    {

        $comment = factory('App\Comment')->create();
        $user = User::find($comment->user_id);

        $this->json('DELETE', '/api/comment/' . $comment->id, [],
            ['Authorization' => 'bearer ' .$user->api_token]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

    }


}
