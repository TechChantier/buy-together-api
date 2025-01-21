<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePurchaseGoalRequest;
use App\Http\Requests\UpdatePurchaseGoalRequest;
use App\Http\Resources\PurchaseGoalResource;
use App\Models\Product;
use App\Models\PurchaseGoal;
use App\Models\UserInPurchaseGoal;
use App\Services\MediaService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Group;

#[Group('Purchase Goals', 'Endpoints for managing purchase goals')]

class PurchaseGoalController extends Controller
{
    protected $relationships = [
        'product',
        'creator',
        'participants',
    ];

    public function __construct(
        protected MediaService $mediaService,
    ) {}

    /**
     * Listing of all purchase goals.
     *
     * This endpoint allows users to view purchase goals.
     * <aside class="notice">
     * Users must NOT be authenticated to access this endpoint.
     * </aside>
     */
    public function index()
    {
        $query = PurchaseGoal::query();

        if (request()->has('search')) {
            $search = request()->query('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%")
                    ->orWhere('target_amount', 'LIKE', "%$search%")
                    ->orWhere('amount_per_person', 'LIKE', "%$search%");
            });
        }

        $purchaseGoals = $query->with($this->relationships)->get();

        return response()->json([
            'success' => true,
            'message' => 'Purchase goals retrieved successfully.',
            'statusCode' => 200,
            'data' => PurchaseGoalResource::collection($purchaseGoals),
        ], 200);
    }

    /**
     * Create a new purchase goal.
     *
     * This endpoint allows users to create a purchase goal.
     * <aside class="notice">
     * Users must be authenticated to access this endpoint.
     * </aside>
     */
    public function store(CreatePurchaseGoalRequest $request)
    {
        $validatedRequest = $request->validated();

        DB::beginTransaction();

        try {
            $purchaseGoal = PurchaseGoal::create(array_merge(
                $validatedRequest,
                ['creator_id' => Auth::id()]
            ));

            $product = Product::create(array_merge([
                'name' => $validatedRequest['product_name'],
                'description' => $validatedRequest['product_description'],
                'quantity' => $validatedRequest['product_quantity'],
                'unit_price' => $validatedRequest['product_unit_price'],
                'bulk_price' => $validatedRequest['product_bulk_price'],
                'purchase_goal_id' => $purchaseGoal->id,
            ]));

            UserInPurchaseGoal::create([
                'user_id' => Auth::id(),
                'purchase_goal_id' => $purchaseGoal->id,
                'joined_at' => now(),
                'status' => 'approved',
            ]);

            if ($request->hasFile('product_image')) {
                $response = $this->mediaService->uploadFile($request->file('product_image'), 'product_images');

                if ($response['success']) {
                    $product->image = $response['path'];
                    $product->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to upload product image',
                        'statusCode' => 500,
                    ], 500);
                }
            }

            DB::commit();

            $purchaseGoal->load($this->relationships);

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal created successfully.',
                'statusCode' => 201,
                'data' => new PurchaseGoalResource($purchaseGoal),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            logger()->error('Failed to create purchase goal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase goal.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch details of a specific purchase goal.
     *
     * This endpoint allows users to view the details of a purchase goal by ID.
     */
    public function show(int $id)
    {
        $purchaseGoal = PurchaseGoal::find($id);

        if (! $purchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase goal not found.',
                'statusCode' => 404,
            ], 404);
        }

        $purchaseGoal->load($this->relationships);

        return response()->json([
            'success' => true,
            'message' => 'Purchase goal retrieved successfully.',
            'statusCode' => 200,
            'data' => new PurchaseGoalResource($purchaseGoal),
        ], 200);
    }

    /**
     * Update an existing purchase goal.
     *
     * This endpoint allows users to update details of a purchase goal.
     * <aside class="notice">
     * Only the owner of the goal can update it.
     * </aside>     
     */
    public function update(UpdatePurchaseGoalRequest $request, int $id)
    {
        DB::beginTransaction();

        try {
            $purchaseGoal = PurchaseGoal::find($id);

            if (! $purchaseGoal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase goal not found.',
                    'statusCode' => 404,
                ], 404);
            }

            $purchaseGoal->update($request->validated());

            $productData = $request->only([
                'product_name',
                'product_description',
                'product_unit_price',
                'product_bulk_price',
                'product_quantity',
                'product_image',
            ]);

            if (! empty($productData)) {
                logger($productData);
                $product = $purchaseGoal->product;

                if ($product) {
                    $product->update([
                        'name' => $productData['product_name'] ?? $product->name,
                        'description' => $productData['product_description'] ?? $product->description,
                        'unit_price' => $productData['product_unit_price'] ?? $product->unit_price,
                        'bulk_price' => $productData['product_bulk_price'] ?? $product->bulk_price,
                        'quantity' => $productData['product_quantity'] ?? $product->quantity,
                    ]);

                    if (! empty($productData['product_image'])) {

                        logger()->info('File Received:', [
                            'name' => $productData['product_image']->getClientOriginalName(),
                            'mime' => $productData['product_image']->getMimeType(),
                        ]);

                        $this->mediaService->deleteFile($product->image);

                        $response = $this->mediaService->uploadFile($productData['product_image'], 'product_images');

                        if ($response['success']) {
                            $product->update(['image' => $response['path']]);
                        } else {
                            throw new \Exception($response['message']);
                        }
                    }
                } else {
                    $product = $purchaseGoal->product()->create([
                        'name' => $productData['product_name'],
                        'description' => $productData['product_description'],
                        'unit_price' => $productData['product_unit_price'],
                        'bulk_price' => $productData['product_bulk_price'],
                        'quantity' => $productData['product_quantity'],
                        'image' => ! empty($productData['product_image']) ? $productData['product_image']->store('product_images', 'public') : null,
                    ]);
                }
            }

            $purchaseGoal->load($this->relationships);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal updated successfully.',
                'statusCode' => 200,
                'data' => new PurchaseGoalResource($purchaseGoal),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            logger()->error('Failed to update purchase goal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase goal.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a purchase goal.
     * 
     * This endpoint allows users to delete a specific purchase goal by ID.
     * <aside class="notice">
     * Only the owner of the goal can delete it.
     * </aside>
     */
    public function destroy(int $id)
    {

        $purchaseGoal = PurchaseGoal::find($id);

        if (! $purchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase goal not found.',
                'statusCode' => 404,
            ], 404);
        }

        if ($purchaseGoal->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this purchase goal.',
                'statusCode' => 403,
            ], 403);
        }

        try {
            $purchaseGoal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal deleted successfully.',
                'statusCode' => 200,
            ], 200);
        } catch (Exception $e) {
            logger()->error('Failed to delete purchase goal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase goal.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change the status of specified purchase goal
     * 
     * <aside class="notice">
     * Only the owner of the goal can change its status from open to close and vice versa.
     * </aside>
     */
    public function changeStatus(int $id)
    {
        $purchaseGoal = PurchaseGoal::find($id);

        if (! $purchaseGoal) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase goal not found.',
                'statusCode' => 404,
            ], 404);
        }

        if ($purchaseGoal->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to change the status of this purchase goal.',
                'statusCode' => 403,
            ], 403);
        }

        DB::transaction();

        try {
            if ($purchaseGoal->status === 'open') {
                $purchaseGoal->status = 'closed';
            } else {
                $purchaseGoal->status = 'open';
            }

            DB::commit();

            $purchaseGoal->save();
            $purchaseGoal->load($this->relationships);

            return response()->json([
                'success' => true,
                'message' => 'Purchase goal status changed successfully.',
                'statusCode' => 200,
                'data' => $purchaseGoal,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            logger()->error('Failed to change purchase goal status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change purchase goal status.',
                'statusCode' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
