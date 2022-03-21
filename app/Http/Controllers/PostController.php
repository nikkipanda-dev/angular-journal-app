<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;

class PostController extends Controller
{
    use ResponseTrait;

    public function store(Request $request) {
        Log::info("Entering PostController store func...");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
            'title' => 'bail|required|string|min:5',
            'body' => 'bail|required|string|min:5',
        ]);

        try {   
            $user = User::find($request->user_id);
            $post = new Post();

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to store record...");
                
                $post->user_id = $request->user_id;
                $post->title = $request->title;
                $post->body = $request->body;

                $post->save();

                if ($post->id) {
                    Log::info("Successfully stored new post ID " . $post->id . ". Leaving PostController store func...\n");

                    return $this->successResponse('post', $post);
                } else {
                    Log::error("Failed to store new post.\n");

                    return $this->errorResponse('Something went wrong. Please try again in a few seconds.');
                }
            } else {
                Log::error("Failed to store new post. User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to store new post. ".$e."\n");

            return $this->errorResponse('Something went wrong. Please try again in a few seconds.');
        }
    }

    public function get(Request $request) {
        Log::info("Entering PostController get func...");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
        ]);

        try {
            $user = User::find($request->user_id);
            $posts = Post::latest()->where('user_id', $request->user_id)->get();

            if ($user) {
                Log::info("User ID ".$user->id." exists. Checking if user has posts...");

                if (count($posts) > 0) {
                    Log::info("Successful. Leaving PostController get func...\n");

                    return $this->successResponse('posts', $posts);
                } else {
                    Log::notice("No post to show.\n");

                    return $this->errorResponse("No post.");
                }
            } else {
                Log::error("Failed to store new post. User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve posts. ".$e.".\n");

            return $this->errorResponse("Something went wrong. Please try again in a few seconds.");
        }
    }

    public function paginate(Request $request) {
        Log::info("Entering PostController paginate func...");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
        ]);

        try {
            $user = User::find($request->user_id);
            $posts = Post::latest()->offset($request->offset)->limit($request->limit)->where('user_id', $request->user_id)->get();

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to retrieve posts..." );

                if (count($posts) > 0) {
                    Log::info("Successful. Leaving PostController paginate func...\n");

                    return $this->successResponse('posts', $posts);
                } else {
                    Log::notice("No post to show.\n");

                    return $this->errorResponse("No post.");
                }
            } else {
                Log::error("Failed to store new post. User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve posts. " . $e . ".\n");

            return $this->errorResponse("Something went wrong. Please try again in a few seconds.");
        }
    }

    public function update(Request $request) {
        Log::info("Entering PostController func...\n");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
            'post_id' => 'bail|required|numeric|exists:posts,id',
            'title' => 'bail|required|string|min:5',
            'body' => 'bail|required|string|min:5',
        ]);

        try {
            $isValid = false;
            $errorText = null;
            $user = User::find($request->user_id);
            $post = Post::find($request->post_id);

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to retrieve record...");

                if ($post) {
                    Log::info("Post ID " . $post->id . " exists. Checking if user ID matches request...");

                    if (intval($post->user_id) === intval($user->id)) {
                        Log::info("Verified. Attempting to soft delete...");

                        $postResponse = DB::transaction(function () use ($post, $request, $isValid, $errorText) {
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
                            Log::info("Successfully updated post ID " . $post->id . ". Leaving PostController func...\n");

                            return $this->successResponse('post', $postResponse['post']);
                        } else {
                            return $this->errorResponse($postResponse['errorText']);
                        }
                    } else {
                        Log::error("User ID and post user_id mismatch.\n");

                        return $this->errorResponse("Unauthorized action.");
                    }
                } else {
                    Log::error("Failed to retrieve post. Post not found.\n");

                    return $this->errorResponse("Post not found.");
                }
            } else {
                Log::error("Failed to update post. User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update post. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }

    public function destroy(Request $request) {
        Log::info("Entering PostController destroy func...");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
            'post_id' => 'bail|required|numeric|exists:posts,id',
        ]);

        try {
            $user = User::find($request->user_id);
            $post = Post::find($request->post_id);

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to retrieve record...");

                if ($post) {
                    Log::info("Post ID " . $post->id . " exists. Checking if user id matches request...");
                    $postId = $post->getOriginal('id');

                    if (intval($post->user_id) == intval($user->id)) {
                        Log::info("Verified. Attempting to soft delete...");

                        $post->delete();

                        if ($post->trashed()) {
                            Log::info("Successfully soft deleted post ID " . $postId . ". Leaving PostController destroy func...");

                            return $this->successResponse('post', 'Post deleted.');
                        } else {
                            Log::error("Failed to soft delete post. Check logs.\n");

                            return $this->errorResponse("Something went wrong.");
                        }
                    } else {
                        Log::error("User ID and post user_id mismatch.\n");

                        return $this->errorResponse("Unauthorized action.");
                    }
                } else {
                    Log::error("Failed to soft delete post. Post not found.\n");

                    return $this->errorResponse("Post not found.");
                }
            } else {
                Log::error("Failed to soft delete post. User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to soft delete post. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }

    public function getRandomPost(Request $request) {
        Log::info("Entering PostController getRandomPost func...");

        $this->validate($request, [
            'user_id' => 'bail|required|numeric|exists:users,id',
        ]);

        try {
            $user = User::find(4);

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to retrieve posts...");

                $posts = Post::latest()->where('user_id', $user->id)->get();

                if (count($posts) > 0) {
                    Log::info("User has posts. Leaving PostController getRandomPost func...");

                    $random = Post::where('user_id', $user->id)->inRandomOrder()->first();

                    return $this->successResponse('post', $random);
                } else {
                    Log::notice("No post at the moment.\n");

                    return $this->errorResponse("No post at the moment.");
                }
            } else {
                Log::error("User not found.\n");

                return $this->errorResponse("Something went wrong.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to get random post. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }
}
