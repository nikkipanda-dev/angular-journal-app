<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
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
    }

    public function get() {
        Log::info("Entering PostController get func...");

        try {
            $posts = Post::latest()->get();

            if (count($posts) > 0) {
                Log::info("Successful. Leaving PostController get func...\n");

                return $this->successResponse('posts', $posts);
            } else {
                Log::notice("No post to show.\n");

                return $this->errorResponse("No post.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve posts. ".$e.".\n");

            return $this->errorResponse("Something went wrong. Please try again in a few seconds.");
        }
    }

    public function update(Request $request) {
        Log::info("Entering PostController func...\n");

        $this->validate($request, [
            'id' => 'bail|required|numeric|exists:posts',
            'title' => 'bail|required|string|min:5',
            'body' => 'bail|required|string|min:5',
        ]);

        try {
            $isValid = false;
            $errorText = null;
            $post = Post::find($request->id);

            if ($post) {
                Log::info("Post ID ".$post->id." exists. Attempting to update...");

                $postResponse = DB::transaction(function () use($post, $request, $isValid, $errorText) {
                    $post->title = $request->title;
                    $post->body = $request->body;

                    $post->save();

                    if ($post->wasChanged()) {
                        $isValid = true;
                    } else {
                        Log::notice("Post details were not changed.\n");
                    }

                    return [
                        'isValid' => $isValid,
                        'errorText' => $errorText,
                        'post' => $post,
                    ];
                }, 3);

                if ($postResponse['isValid']) {
                    Log::info("Successfully updated post ID ".$post->id.". Leaving PostController func...\n");

                    return $this->successResponse('post', $postResponse['post']);
                } else {
                    return $this->errorResponse($postResponse['errorText']);
                }
            } else {
                Log::error("Failed to retrieve post. Post not found.\n");

                return $this->errorResponse("Post not found.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update post. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }

    public function destroy(Request $request) {
        Log::info("Entering PostController destroy func...");

        $this->validate($request, [
            'id' => 'bail|required|numeric|exists:posts',
        ]);

        try {
            $post = Post::find($request->id);

            if ($post) {
                Log::info("Post ID ".$post->id." exists. Attempting to soft delete...");
                $postId = $post->getOriginal('id');

                $post->delete();

                if ($post->trashed()) {
                    Log::info("Successfully soft deleted post ID ".$postId.". Leaving PostController destroy func...");

                    return $this->successResponse('post', 'Post deleted.');
                } else {
                    Log::error("Failed to soft delete post. Check logs.\n");

                    return $this->errorResponse("Something went wrong.");
                }
            } else {
                Log::error("Failed to soft delete post. Post not found.\n");

                return $this->errorResponse("Post not found.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to soft delete post. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }
}
