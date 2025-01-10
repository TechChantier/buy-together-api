<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\MediaService;
use Exception;
use Illuminate\Support\Facades\DB;

class AuthenticationController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
    ) {}

    /**
     * User registration
     */
    public function register(RegisterRequest $request)
    {
        logger($request);
        $validatedRequest = $request->validated();

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $validatedRequest['name'],
                'email' => $validatedRequest['email'],
                'phone_number' => $validatedRequest['phone_number'],
                'password' => bcrypt($validatedRequest['password']),
                'address' => $validatedRequest['address'],
            ]);

            if ($request->hasFile('avatar')) {
                $response = $this->mediaService->uploadFile($request->file('avatar'), 'avatars');

                if ($response['success']) {
                    $user->avatar = $response['path'];
                    $user->save();
                } else {
                    throw new Exception($response['message']);
                }
            }
 
            DB::commit();

            $user->load('purchaseGoals');

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            logger()->error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * User authentication
     */
    public function login(LoginRequest $request) {}

    /**
     * User logout from application
     */
    public function logout() {}
}
