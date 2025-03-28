<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseGoalResource;
use App\Http\Resources\UserInPurchaseGoalResource;
use App\Http\Resources\UserResource;
use App\Models\PurchaseGoal;
use App\Models\User;
use App\Models\UserInPurchaseGoal;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Group;

#[Group('User Participation', 'Endpoints for managing user participation in purchase goals')]
class ParticipationController extends Controller
{
    protected $relationships = [
        'user',
        'purchaseGoal',
    ];

    /**
     * Get participants of a purchase goal.
     *
     * Retrieve all participants of a specified purchase goal.
     */
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

    /**
     * Join a purchase goal.
     *
     * Allows a user to join a specific purchase goal.
     */
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

    /**
     * Approve a purchase goal.
     *
     * Allows a user to APPROVE another user to join purchase goal.
     */
    public function approve(int $purchaseGoalId, int $userId)
    {
        $purchaseGoal = PurchaseGoal::find($purchaseGoalId);
        $user = User::find($userId);
        logger($user);
        if (Auth::id() !== $purchaseGoal->creator_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to decline join requests.',
                'statusCode' => 401,
            ], 401);
        }
        $joinRequest = $purchaseGoal->participants()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $joinRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No pending join request found for this user.',
                'statusCode' => 404,
            ], 404);
        }

        try {
            $joinRequest->pivot->update(['status' => 'approved']);

            return response()->json([
                'success' => true,
                'message' => 'Join request approved successfully.',
                'statusCode' => 200,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Failed to approve join request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve join request.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decline request to join a purchase goal.
     *
     * Allows user to DECLINE a request from another user to join purchase goal.
     */
    public function decline(int $purchaseGoalId, int $userId)
    {
        $purchaseGoal = PurchaseGoal::find($purchaseGoalId);
        $user = User::find($userId);
        if (Auth::id() !== $purchaseGoal->creator_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to decline join requests.',
                'statusCode' => 401,
            ], 401);
        }
        $joinRequest = $purchaseGoal->participants()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $joinRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No pending join request found for this user.',
                'statusCode' => 404,
            ], 404);
        }

        try {
            $joinRequest->pivot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Join request declined successfully.',
                'statusCode' => 200,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Failed to decline join request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to decline join request.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all purchase goals a user is participating in.
     *
     * Retrieves a list of all purchase goals the authenticated user is part of,
     * along with their status and contribution details.
     */
    public function userPurchaseGoals()
    {
        try {
            $user = Auth::user();
            $purchaseGoals = $user->purchaseGoalsParticipated;

            $purchaseGoals->load([
                'creator',
                'product'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User purchase goals retrieved successfully.',
                'statusCode' => 200,
                'data' => PurchaseGoalResource::collection($purchaseGoals)
            ], 200);

        } catch (Exception $e) {
            logger()->error('Failed to retrieve user purchase goals', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user purchase goals.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
