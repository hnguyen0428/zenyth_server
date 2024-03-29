<?php

namespace Tests\Feature;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Image;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PinpostTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPinpostCreation()
    {
        //create pseudouser
        $api_token = factory('App\User')->create()->api_token;

        //perform the json request
        $this->json('POST', '/api/pinpost', [
            'title' => 'testpin',
            'description' => 'fake description for fake pins',
            'latitude' => 33.33,
            'longitude' => 69.69
        ], ['Authorization' => 'bearer ' . $api_token]);

        $this->assertDatabaseHas('pinposts', [
            'title' => 'testpin',
            'description' => 'fake description for fake pins',
            'latitude' => 33.33,
            'longitude' => 69.69
        ]);
    }

    public function testTagCreation()
    {
        //create pesudouser
        $api_token = factory('App\User')->create()->api_token;

        //perform the json request
        $this->json('POST', '/api/pinpost', [
            'title' => 'testpin',
            'description' => 'fake description for fake pins',
            'latitude' => 33.33,
            'longitude' => 69.69,
            'tags' => 'hello,hi,howareyou,imokay'
        ], ['Authorization' => 'bearer ' . $api_token]);

        $this->assertDatabaseHas('tags', ['name' => 'hello']);
        $this->assertDatabaseHas('tags', ['name' => 'hi']);
        $this->assertDatabaseHas('tags', ['name' => 'howareyou']);
        $this->assertDatabaseHas('tags', ['name' => 'imokay']);
    }

    public function testPinpostRead()
    {

        //create a pinpost, with the title 'pintoread' and no image
        $pinpost = factory('App\Pinpost')->create(['title' => 'pintoread']);
        $response = $this->json('GET', '/api/pinpost/read/' . $pinpost->id,
            [],
            ['Authorization' => 'bearer ' . 'token']);

        $response->assertJson([
            'success' => true,
            'data' => [
                'pinpost' => [
                    'title' => $pinpost->title,
                    'description' => $pinpost->description,
                    'latitude' => $pinpost->latitude,
                    'longitude' => $pinpost->longitude
                ]
            ]
        ]);

    }

    public function testPinpostUpdate()
    {

        //create a pinpost, with the title 'pintoupdate' and no image
        $pinpost = factory('App\Pinpost')->create(['title' => 'pintoupdate']);

        //post request to update the created pin with new values
        $this->json('PATCH', '/api/pinpost/' . $pinpost->id, [
            'title' => 'updatedpin',
            'description' => 'fake description for fake pins',
            'latitude' => 33.33,
            'longitude' => 69.69,
        ], ['Authorization' => 'bearer ' . User::find($pinpost->user_id)->api_token]);

        //check if pin title has been changed
        $this->assertDatabaseHas('pinposts', [
            'title' => 'updatedpin',
            'latitude' => 33.33,
            'longitude' => 69.69
        ]);

    }

    public function testPinpostDelete()
    {

        //create a pinpost, with the title 'pintodelete' and no image
        $pinpost = factory('App\Pinpost')->create(['title' => 'pintodelete']);

        $this->assertDatabaseHas('pinposts', ['title' => 'pintodelete']);

        $this->json('DELETE', '/api/pinpost/' . $pinpost->id, [],
            ['Authorization' => 'bearer ' . User::find($pinpost->user_id)
                    ->api_token]);

        $this->assertDatabaseMissing('pinposts', ['title' => 'pintodelete']);

        $pinpost = factory('App\Pinpost')->create(['title' => 'fail to delete']);
        $user = factory('App\User')->create();
        $response = $this->json('DELETE', '/api/pinpost/' .
            $pinpost->id, [],
            ['Authorization' => 'bearer ' . $user->api_token]);
        $response
            ->assertJson([
                'success' => false,
                'error' => [
                    'type' => 'InvalidTokenException'
                ]
            ]);
        $this->assertDatabaseHas('pinposts', ['title' => 'fail to delete']);

        $pinpost = factory('App\Pinpost')->create(['title' => 'test pin to delete cascade']);
        $comment = factory('App\Comment')->create(['commentable_id' =>
            $pinpost->id, 'comment' => 'test comment to delete cascade']);
        $likeOne = factory('App\Like')->create(['likeable_id' => $pinpost->id]);
        $likeTwo = factory('App\Like')->create(['likeable_id' =>
            $comment->id, 'likeable_type' => 'App\Comment']);

        $this->assertDatabaseHas('pinposts', ['id' => $pinpost->id]);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('likes', ['likeable_id' => $pinpost->id]);
        $this->assertDatabaseHas('likes', ['likeable_id' => $comment->id]);

        $response = $this->json('DELETE', '/api/pinpost/' .
            $pinpost->id, [],
            ['Authorization' => 'bearer ' . User::find($pinpost->user_id)->api_token]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pinposts', ['id' => $pinpost->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('likes', ['id' => $likeOne->id]);
        $this->assertDatabaseMissing('likes', ['id' => $likeTwo->id]);
    }

    public function testPinpostFetch()
    {
        $api_token = factory('App\User')->create()->api_token;
        $this->json('POST', '/api/pinpost', [
            'title' => 'testpin fetch',
            'description' => 'description for pinpost',
            'latitude' => 30,
            'longitude' => 30
        ], ['Authorization' => 'bearer ' . $api_token]);

        // Test for fetching pinpost within radius in miles
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=92&scope=public', [],
            ['Authorization' => 'bearer ' . $api_token]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'pinposts' => [
                    ['title' => 'testpin fetch']
                ]
            ]
        ]);

        // Test for fetching pinpost not within radius in miles
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=91&scope=public', [],
            ['Authorization' => 'bearer ' . $api_token]);

        $response->assertJsonMissing([
            'data' => [
                'pinposts' => ['title' => 'testpin fetch']
            ]
        ]);

        // Test for fetching pinpost within radius in km
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=147&scope=public&unit=km', [],
            ['Authorization' => 'bearer ' . $api_token]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'pinposts' => [
                    ['title' => 'testpin fetch']
                ]
            ]
        ]);

        // Test for fetching pinpost not within radius in km
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=146&scope=public&unit=km', [],
            ['Authorization' => 'bearer ' . $api_token]);

        $response->assertJsonMissing([
            'data' => [
                'pinposts' => ['title' => 'testpin fetch']
            ]
        ]);

        // Test for fetching pinpost within frame
        $response = $this->json('GET', '/api/pinpost/fetch?type=frame&top_left=29,32&bottom_right=31,29&scope=public', [],
            ['Authorization' => 'bearer ' . $api_token]);

        $response->assertJson([
            'success' => true,
            'data' => [
                'pinposts' => [
                    ['title' => 'testpin fetch']
                ]
            ]
        ]);

        // Test for fetching pinpost not within frame
        $response = $this->json('GET', '/api/pinpost/fetch?type=frame&top_left=28,33&bottom_right=32,31&scope=public', [],
            ['Authorization' => 'bearer ' . $api_token]);
        $response->assertJsonMissing([
            'data' => [
                'pinposts' => ['title' => 'testpin fetch']
            ]
        ]);
    }

    public function testPinpostFilterByPrivacy()
    {
        // Test for filtering pinpost so that only the creator can see it
        $userOne = factory('App\User')->create();
        $this->json('POST', '/api/pinpost', [
            'title' => 'testpin self',
            'description' => 'description for pinpost with self privacy',
            'latitude' => 30,
            'longitude' => 30,
            'tags' => 'selfscopetag',
            'privacy' => 'self'
        ], ['Authorization' => 'bearer ' . $userOne->api_token]);

        $userTwo = factory('App\User')->create();
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=91&scope=public', [],
            ['Authorization' => 'bearer ' . $userTwo->api_token]);

        $response->assertJsonMissing([
            'data' => [
                'pinposts' => ['title' => 'testpin self']
            ]
        ]);

        // Test for filtering pinpost so that only the creator's friends can
        // see it
        $this->json('POST', '/api/pinpost', [
            'title' => 'testpin friends',
            'description' => 'description for pinpost with friends privacy',
            'latitude' => 30,
            'longitude' => 30,
            'tags' => 'friendsscopetag',
            'privacy' => 'friends'
        ], ['Authorization' => 'bearer ' . $userOne->api_token]);

        // Before userTwo becomes userOne's friend, userTwo should not be
        // able to see the pinpost
        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=100&scope=public', [],
            ['Authorization' => 'bearer ' . $userTwo->api_token]);

        $response->assertJsonMissing([
            'data' => [
                'pinposts' => ['title' => 'testpin friends']
            ]
        ]);

        // After userTwo becomes userOne's friend, userTwo can see the pinpost
        factory('App\Relationship')->create([
            'requester' => $userOne->id,
            'requestee' => $userTwo->id,
            'status' => true
        ]);

        $response = $this->json('GET', '/api/pinpost/fetch?type=radius&center=31,31&radius=100&scope=public', [],
            ['Authorization' => 'bearer ' . $userTwo->api_token]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'pinposts' => [
                    ['title' => 'testpin friends']
                ]
            ]
        ]);
    }

}
