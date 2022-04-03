<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\User;
use App\Models\Image;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'image' => 'bail|nullable|image',
        ]);

        try {   
            $user = User::find($request->user_id);
            $post = new Post();
            $image = new Image();
            $isValid = false;
            $errorText = null;

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to store record...");

                $postResponse = DB::transaction(function() use($request, $post, $image, $user, $isValid, $errorText) {
                    $post->user_id = $request->user_id;
                    $post->title = $request->title;
                    $post->body = $request->body;

                    $post->save();

                    if ($request->hasFile('image')) {
                        Log::info("Image has file. Checking if valid...");

                        if ($request->image->isValid()) {
                            Log::info("Image is valid. Checking if filename is unique...");

                            $randNum = '';

                            for ($i = 0; $i < 10; $i++) {
                                $randNum .= mt_rand(0, 9);
                            }

                            $filename = $user->id . '-' . $post->id . '-' . $randNum.'.'. $request->image->extension();

                            if (Storage::disk('public')->missing('posts/' . $filename)) {
                                Storage::putFileAs(
                                    'public/posts',
                                    $request->image,
                                    $filename
                                );

                                // Check if image was saved to storage
                                if (Storage::disk('public')->exists('posts/' . $filename)) {
                                    $image->post_id = $post->id;
                                    $image->path = 'storage/posts/' . $filename;

                                    $image->save();

                                    if ($image->id) {
                                        Log::info("Image path successfully saved with ID " . $image->id . ".");
                                    } else {
                                        Log::error("Failed to store image path to database. Check logs.");

                                        $errorText = "Failed to image path to database. Please try again in a few minutes or contact us for assistance.";

                                        throw new Exception("Failed to store image path to database.\n");
                                    }
                                } else {
                                    Log::error("Generated filename is unique but failed to store image to storage. Check logs.");

                                    $errorText = "Failed to create post. Something went wrong. Please try again in a few seconds.";

                                    throw new Exception("Generated filename is unique but failed to store image to storage.\n");
                                }
                            } else {
                                Log::notice('Filename for image already exists in the storage folder /posts.');

                                $errorText = "Failed to create post. Please try again in a few minutes or contact us for assistance.";

                                throw new Exception("Failed to store image to storage. Filename for image already exists in the storage folder /posts.\n");
                            }
                        } else {
                            Log::error("Failed to store post. Image is invalid.\n");

                            $errorText = "Failed to create post. Image is invalid.";

                            throw new Exception($errorText);
                        }
                    } else {
                        Log::notice("No uploaded file. Skipping...");
                    }

                    if ($post->id) {
                        $isValid = true;
                    } else {
                        Log::error("Failed to store new post.\n");

                        $errorText = "Something went wrong. Please try again in a few seconds.";
                    }

                    return [
                        'isValid' => $isValid,
                        'errorText' => $errorText,
                        'post' => $post,
                    ];
                }, 3);

                if ($postResponse['isValid']) {
                    Log::info("Successfully stored new post ID " . $postResponse['post']['id']. ". Leaving PostController store func...\n");

                    return $this->successResponse('post', $postResponse['post']);
                } else {
                    Log::error("Failed to store post.\n");

                    return $this->errorResponse($postResponse['errorText']);
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
            $posts = Post::latest()->with('images')->where('user_id', $request->user_id)->get();

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
            $posts = Post::latest()->with('images')->offset($request->offset)->limit($request->limit)->where('user_id', $request->user_id)->get();

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
            'image' => 'bail|nullable|image',
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

                        $postResponse = DB::transaction(function () use ($post, $user, $request, $isValid, $errorText) {
                            $post->title = $request->title;
                            $post->body = $request->body;

                            $post->save();

                            if ($request->hasFile('image')) {
                                Log::info("Image has file. Checking if valid...");

                                if ($request->image->isValid()) {
                                    Log::info("Image is valid. Checking if filename is unique...");

                                    $randNum = '';

                                    for ($i = 0; $i < 10; $i++) {
                                        $randNum .= mt_rand(0, 9);
                                    }

                                    $filename = $user->id . '-' . $post->id . '-' . $randNum . '.' . $request->image->extension();

                                    if (Storage::disk('public')->missing('posts/' . $filename)) {
                                        Storage::putFileAs(
                                            'public/posts',
                                            $request->image,
                                            $filename
                                        );

                                        // Check if image was saved to storage
                                        if (Storage::disk('public')->exists('posts/' . $filename)) {

                                            $image = Image::where('post_id', $post->id)->first();

                                            // Check if an image already exists
                                            if ($image) {
                                                $currentPath = $image->getOriginal('path');
                                                Log::info("Current path: " . $currentPath);

                                                $image->post_id = $post->id;
                                                $image->path = 'storage/posts/' . $filename;

                                                $image->save();

                                                if ($image->wasChanged('path')) {
                                                    $isValid = true;
                                                    Log::info("Image path successfully saved with ID " . $image->id . ".");

                                                    if ($currentPath) {
                                                        if (str_starts_with($currentPath, 'storage/posts/')) {
                                                            $pos = strpos($currentPath, 'storage/posts/');
                                                            if ($pos !== false) {
                                                                $newstring = substr_replace($currentPath, '', $pos, strlen('storage/posts/'));
                                                                $currentPath && Storage::disk('public')->delete('posts/' . $newstring);
                                                            }
                                                        } else {
                                                            $currentPath && Storage::disk('public')->delete('posts/' . $currentPath);
                                                        }
                                                    }
                                                } else {
                                                    Log::error("Failed to store image path to database. Check logs.");

                                                    $errorText = "Failed to image path to database. Please try again in a few minutes or contact us for assistance.";

                                                    throw new Exception("Failed to store image path to database.\n");
                                                }
                                            } else {
                                                Log::notice("No existing image. Storing new...");

                                                $image = new Image();

                                                $image->post_id = $post->id;
                                                $image->path = 'storage/posts/' . $filename;

                                                $image->save();

                                                if ($image->id) {
                                                    $isValid = true;
                                                    Log::info("Image successfully stored to database.\n");
                                                } else {
                                                    Log::error("Failed to store image path to database. Check logs.");

                                                    $errorText = "Failed to image path to database. Please try again in a few minutes or contact us for assistance.";

                                                    throw new Exception("Failed to store image path to database.\n");
                                                }
                                            }
                                        } else {
                                            Log::error("Generated filename is unique but failed to store image to storage. Check logs.");

                                            $errorText = "Failed to create post. Something went wrong. Please try again in a few seconds.";

                                            throw new Exception("Generated filename is unique but failed to store image to storage.\n");
                                        }
                                    } else {
                                        Log::notice('Filename for image already exists in the storage folder /posts.');

                                        $errorText = "Failed to create post. Please try again in a few minutes or contact us for assistance.";

                                        throw new Exception("Failed to store image to storage. Filename for image already exists in the storage folder /posts.\n");
                                    }
                                } else {
                                    Log::error("Failed to store post. Image is invalid.\n");

                                    $errorText = "Failed to create post. Image is invalid.";

                                    throw new Exception($errorText);
                                }
                            } else {
                                Log::notice("No uploaded file. Skipping...");
                            }

                            if ($post->wasChanged()) {
                                $isValid = true;
                            } else {
                                Log::notice("Post details were not changed.\n");

                                $errorText = "Post not changed.";
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
            $user = User::find($request->user_id);

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
