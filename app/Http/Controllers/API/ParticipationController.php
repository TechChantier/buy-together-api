<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserInPurchaseGoalResource;
use App\Http\Resources\UserResource;
use App\Models\PurchaseGoal;
use App\Models\UserInPurchaseGoal;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipationController extends Controller
{
    protected $relationships = [
        'user',
        'purchaseGoal',
    ];

    public function purchaseGoalParticipants(int $id)
    {
        $purchaseGoal = PurchaseGoal::find($id);

        if (! $purchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase goal not found.',
                'statusCode' => 404,
            ], 404);
        }

        if (Auth::id() != $purchaseGoal->creator_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view participants.',
                'statusCode' => 401,
            ], 401);
        }

        try {
            $participants = $purchaseGoal->participants;

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal participants retrieved successfully.',
                'statusCode' => 200,
                'data' => UserResource::collection($participants),
            ], 200);

        } catch (Exception $e) {
            logger()->error('Failed to retrieve purchase goal participants', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve purchase goal participants.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function join(int $id)
    {
        $purchaseGoal = PurchaseGoal::find($id);

        if (! $purchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase goal not found.',
                'statusCode' => 404,
            ], 404);
        }

        $userInPurchaseGoal = UserInPurchaseGoal::where('user_id', Auth::id())
            ->where('purchase_goal_id', $purchaseGoal->id)
            ->first();

        if ($userInPurchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'You have already joined this purchase goal!',
                'statusCode' => 409,
            ], 409);
        }

        if (Auth::id() == $purchaseGoal->creator_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot join your purchase goal.',
                'statusCode' => 403,
            ], 403);
        }

        DB::beginTransaction();

        try {
            $userInPurchaseGoal = UserInPurchaseGoal::create([
                'user_id' => Auth::id(),
                'purchase_goal_id' => $purchaseGoal->id,
                'joined_at' => now(),
                'status' => 'pending',
            ]);
            DB::commit();

            $userInPurchaseGoal->load($this->relationships);

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal joined successfully.',
                'statusCode' => 200,
                'data' => new UserInPurchaseGoalResource($userInPurchaseGoal),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            logger()->error('Failed to join purchase goal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to join purchase goal.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
