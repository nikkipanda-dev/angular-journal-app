<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Traits\ResponseTrait;

class AccountController extends Controller
{
    use ResponseTrait;

    public function register(Request $request) {
        Log::info("Entering AccountController register func...");

        $this->validate($request, [
            'email' => 'bail|required|email|unique:users',
            'password' => 'bail|required|string|min:8|max:16|confirmed',
            'password_confirmation' => 'required'
        ]);

        try {
            $user = new User();

            $user->name = 'n/a';
            $user->email = $request->email;
            $user->password = Hash::make($request->password);

            $user->save();

            if ($user->id) {
                Log::info("Successfully stored new user ID ".$user->id. ". Issuing token and leaving AccountController register func...\n");

                $token = $user->createToken('journal_app_secret_user')->plainTextToken;

                return $this->successResponse('user', [
                    'token' => $token,
                    'details' => $user,
                ]);
            } else {
                Log::error("Failed to store new user ID. Check logs.\n");
            }
        } catch (\Exception $e) {
            Log::error("Failed to store new account. ".$e.".\n");

            return $this->errorResponse("Something went wrong. Please try again in a few seconds.");
        }
    }

    public function authenticate(Request $request) {
        Log::info("Entering AccountController authenticate func...");

        $this->validate($request, [
            'email' => 'bail|required|email',
            'password' => 'bail|required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                Log::info("User ID ".$user->id."exists. Authenticating...");

                if (Hash::check($request->password, $user->password)) {
                    Log::info("Authenticated. Issuing token...");

                    $token = $user->createToken('journal_app_secret_user')->plainTextToken;

                    return $this->successResponse('user', [
                        'token' => $token,
                        'details' => $user,
                    ]);
                } else {
                    Log::error("Failed to authenticate user. User not found.\n");

                    return $this->errorResponse("Log in failed. Make sure your credentials are correct then try again.");
                }
            } else {
                Log::error("Failed to authenticate user. User not found.\n");

                return $this->errorResponse("Log in failed. Make sure your credentials are correct then try again.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to authenticate user. ".$e."\n");

            return $this->errorResponse("Something went wrong. Please again in a few seconds.");
        }
    }

    public function logout(Request $request) {
        Log::info("Entering AccountController logout func...");

        $this->validate($request, [
            'id' => 'bail|required|numeric|exists:users',
        ]);

        try {
            $user = User::find($request->id);

            if ($user) {
                Log::info("User ID ".$user->id." exists. Attempting to delete token...");

                if ($user->tokens()->delete()) {
                    Log::info("Successfully logged out. Leaving AccountController logout func...");

                    return $this->successResponse('user', 'You are now logged out.');
                } else {
                    Log::error("Failed to log out. Check database.\n");

                    return $this->errorResponse("Something went wrong.");
                }
            } else {
                Log::error("Failed to log out. User not found.\n");

                return $this->errorResponse("User not found.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to log out. ".$e->getMessage().".\n");

            return $this->errorResponse("Something went wrong.");
        }
    }
}
