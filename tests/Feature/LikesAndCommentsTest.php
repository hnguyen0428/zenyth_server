<?php

namespace Tests\Feature;

use App\Entity;
use App\User;
use App\Like;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LikesAndCommentsTest extends TestCase
{

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->createApplication();

        Artisan::call('migrate:refresh');

        Artisan::call('db:seed', ['--class' => 'LikesCommentsTableSeeder']);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLikeCreation()
    {

        $user = User::first();
        $entity = factory('App\Entity')->create();

        $this->assertEquals(0, $entity->likesCount());

        $this->json('POST', '/api/like', ['entity_id' => $entity->id], ['Authorization' => $user->api_token]);

        $this->assertEquals(1, $entity->likesCount());

    }

    public function testLikeDeletion()
    {

        $like = Like::first();

        $entity = Entity::find($like->entity_id);
        $numLikes = $entity->likesCount();

        $this->json('DELETE', '/api/like/' . $entity->id, [], ['Authorization' => $like->user->api_token]);

        $this->assertEquals($numLikes-1, $entity->likesCount());

    }

    public function testCommentCreation()
    {

        $user = User::first();

        $entity = factory('App\Entity')->create();

        $this->assertEquals(0, $entity->commentsCount());

        $this->json('POST', '/api/comment', ['entity_id' => $entity->id], ['Authorization' => $user->api_token]);

        $this->assertEquals(1, $entity->commentsCount());

    }

    public function testCommentRead()
    {

        do {
            $entity = Entity::inRandomOrder()->first();
        } while ($entity->commentsCount() == 0);

        $comment = $entity->comments->first();

        $this->json('GET', '/api/comment/' . $comment->id);

        $this->assertEquals(1, $entity->commentsCount());

    }

    public function testCommentUpdate()
    {

        $comment = factory('App\Comment')->create();

        $text = $comment->comment;

        $this->json('POST', '/api/comment/' . $comment->id, ['comment' => 'NewText!!'], ['Authorization' => $comment->user->api_token]);

        $this->assertNotEquals($text, $comment->comment);
        $this->assertEquals('NewText!!', $comment->comment);
        $this->assertDatabaseHas('comments', ['comment' => 'NewText!!']);

    }

    public function testCommentDelete()
    {

        $comment = factory('App\Comment')->create();

        $this->json('DELETE', '/api/comment/' . $comment->id, [], ['Authorization' => $comment->user->api_token]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

    }

    public function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
        Artisan::call('migrate:refresh');
    }

}
