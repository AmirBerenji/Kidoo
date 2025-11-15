<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Nanny;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class ReviewController extends Controller
{
    /**
     * Store a review
     *
     * @param StoreReviewRequest $request
     * @return JsonResponse
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {

        $validated = $request->validated();
        $type = $request->input('type'); // 'nurse' or 'doctor'
        $reviewableId = $request->input('reviewable_id');

        // Get the model instance
        $reviewable = $this->getReviewableModel($type, $reviewableId);

        if (!$reviewable) {
            return apiResponse(
                false,
                ucfirst($type) . ' not found.',
                null,
                404
            );
        }

        try {
            // Check if user already reviewed
            $existingReview = Review::where('reviewable_id', $reviewable->id)
                ->where('reviewable_type', get_class($reviewable))
                ->where('user_id', Auth::id())
                ->first();

            if ($existingReview) {
                return apiResponse(
                    false,
                    "You have already submitted a review for this {$type}.",
                    null,
                    409
                );
            }

            // Create review
            $review = Review::create([
                'reviewable_id' => $reviewable->id,
                'reviewable_type' => get_class($reviewable),
                'user_id' => Auth::id(),
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            $review->load('user:id,name,email');

            return apiResponse(
                true,
                'Review submitted successfully',
                [
                    'review' => $review,
                    'average_rating' => round($reviewable->averageRating(), 2),
                    'total_reviews' => $reviewable->totalReviews()
                ],
                201
            );

        } catch (QueryException $e) {
            // Handle duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                return apiResponse(
                    false,
                    "You have already submitted a review for this {$type}.",
                    null,
                    409
                );
            }

            return apiResponse(
                false,
                'An error occurred while submitting the review.',
                null,
                500
            );
        }
    }

    /**
     * Get reviews for a reviewable (nurse or doctor)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {

        $type = $request->input('type'); // 'nurse' or 'doctor'
        $reviewableId = $request->input('reviewable_id');

        // Get the model instance
        $reviewable = $this->getReviewableModel($type, $reviewableId);

        if (!$reviewable) {
            return apiResponse(
                false,
                ucfirst($type) . ' not found.',
                null,
                404
            );
        }

        $reviews = $reviewable->reviews()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return apiResponse(
            true,
            'Reviews retrieved successfully',
            [
                'reviews' => $reviews,
                'average_rating' => round($reviewable->averageRating() ?? 0, 2),
                'total_reviews' => $reviewable->totalReviews()
            ]
        );
    }

    /**
     * Update a review
     *
     * @param StoreReviewRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreReviewRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();

        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $review->load('user:id,name,email');

        return apiResponse(
            true,
            'Review updated successfully',
            [
                'review' => $review,
                'average_rating' => round($review->reviewable->averageRating(), 2),
                'total_reviews' => $review->reviewable->totalReviews()
            ]
        );
    }

    /**
     * Delete a review
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $review = Review::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $reviewable = $review->reviewable;
        $review->delete();

        return apiResponse(
            true,
            'Review deleted successfully',
            [
                'average_rating' => round($reviewable->averageRating() ?? 0, 2),
                'total_reviews' => $reviewable->totalReviews()
            ]
        );
    }

    /**
     * Check if user has reviewed a specific nurse or doctor
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkUserReview(Request $request): JsonResponse
    {
        $type = $request->input('type'); // 'nurse' or 'doctor'
        $reviewableId = $request->input('reviewable_id');

        // Get the model instance
        $reviewable = $this->getReviewableModel($type, $reviewableId);

        if (!$reviewable) {
            return apiResponse(
                false,
                ucfirst($type) . ' not found.',
                null,
                404
            );
        }

        $review = Review::where('reviewable_id', $reviewable->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('user_id', Auth::id())
            ->with('user:id,name,email')
            ->first();

        return apiResponse(
            true,
            'Review status retrieved successfully',
            [
                'has_reviewed' => !is_null($review),
                'review' => $review
            ]
        );
    }

    /**
     * Get reviewable model instance based on type
     *
     * @param string $type
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private function getReviewableModel(string $type, int $id)
    {
        return match ($type) {
            'nurse' => Nanny::find($id),
            // 'doctor' => Doctor::find($id),
            default => null,
        };
    }
}
