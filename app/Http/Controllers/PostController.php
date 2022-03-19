<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Traits\ResponseTrait;
use Exception;

class PostController extends Controller
{
    use ResponseTrait;

    public function store(Request $request) {
        Log::info("Entering PostController store func...");

        $this->validate($request, [
            'title' => 'bail|required|string|min:5',
            'body' => 'bail|required|string|min:5',
        ]);

        try {   
            $post = new Post();

            $post->title = $request->title;
            $post->body = $request->body;

            $post->save();

            if ($post->id) {
                Log::info("Successfully stored new post ID ".$post->id. ". Leaving PostController store func...\n");

                return $this->successResponse('post', $post);
            } else {
                Log::error("Failed to store new post.\n");

                return $this->errorResponse('Something went wrong. Please try again in a few seconds.');
            }
        } catch (\Exception $e) {
            Log::error("Failed to store new post. ".$e."\n");

            return $this->errorResponse('Something went wrong. Please try again in a few seconds.');
        }

        return response('ok', 200);
    }

    public function get() {
        Log::info("Entering PostController get func...");

        try {
            $posts = Post::latest()->get();

            if (count($posts) > 0) {
                Log::info("Successful. Leaving PostController get func...\n");

                return $this->successResponse('posts', $posts);
            } else {
                Log::notice("No post.\n");

                return $this->errorResponse("No post.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve posts. ".$e.".\n");

            return $this->errorResponse("Something went wrong. Please try again in a few seconds.");
        }
    }
}
