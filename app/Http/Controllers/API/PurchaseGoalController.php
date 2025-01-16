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

class PurchaseGoalController extends Controller
{
    protected $relationships = [
        'product',
        'creator',
    ];

    public function __construct(
        protected MediaService $mediaService,
    ) {}

    /**
     * Display a listing of the purchase goals.
     */
    public function index()
    {
        $purchaseGoals = PurchaseGoal::where('creator_id', Auth::id())->get();
        $purchaseGoals->load($this->relationships);

        return response()->json([
            'success' => true,
            'message' => 'Purchase goals retrieved successfully.',
            'statusCode' => 200,
            'data' => PurchaseGoalResource::collection($purchaseGoals),
        ], 200);
    }

    /**
     * Store a newly created purchase goal in storage.
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
     * Display the specified purchase goal.
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
     * Update the specified purchase goal in storage.
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
     * Remove the specified purchase goal from storage.
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
