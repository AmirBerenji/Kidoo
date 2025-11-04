<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Doctor;
use App\Models\Nurse;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;

class ReviewController extends Controller
{
    /**
     * Store a review for a doctor
     */
    public function storeDoctorReview(StoreReviewRequest $request, $doctorId): JsonResponse
    {
        $doctor = Doctor::findOrFail($doctorId);

        return $this->storeReview($request, $doctor, 'doctor');
    }

    /**
     * Store a review for a nurse
     */
    public function storeNurseReview(StoreReviewRequest $request, $nurseId): JsonResponse
    {
        $nurse = Nurse::findOrFail($nurseId);

        return $this->storeReview($request, $nurse, 'nurse');
    }

    /**
     * Common method to store review
     */
    private function storeReview(StoreReviewRequest $request, $reviewable, string $type): JsonResponse
    {
        try {
            // Check if user already reviewed
            $existingReview = Review::where('reviewable_id', $reviewable->id)
                ->where('reviewable_type', get_class($reviewable))
                ->where('user_id', Auth::id())
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already submitted a review for this {$type}."
                ], 409); // 409 Conflict
            }

            // Create review
            $review = Review::create([
                'reviewable_id' => $reviewable->id,
                'reviewable_type' => get_class($reviewable),
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            $review->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => [
                    'review' => $review,
                    'average_rating' => round($reviewable->averageRating(), 2),
                    'total_reviews' => $reviewable->totalReviews()
                ]
            ], 201);

        } catch (QueryException $e) {
            // Handle duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already submitted a review for this {$type}."
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the review.'
            ], 500);
        }
    }

    /**
     * Update a review
     */
    public function updateReview(StoreReviewRequest $request, $reviewId): JsonResponse
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => [
                'review' => $review,
                'average_rating' => round($review->reviewable->averageRating(), 2),
                'total_reviews' => $review->reviewable->totalReviews()
            ]
        ]);
    }

    /**
     * Delete a review
     */
    public function deleteReview($reviewId): JsonResponse
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $reviewable = $review->reviewable;
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
            'data' => [
                'average_rating' => round($reviewable->averageRating() ?? 0, 2),
                'total_reviews' => $reviewable->totalReviews()
            ]
        ]);
    }

    /**
     * Get reviews for a doctor
     */
    public function getDoctorReviews($doctorId): JsonResponse
    {
        $doctor = Doctor::findOrFail($doctorId);

        return $this->getReviews($doctor);
    }

    /**
     * Get reviews for a nurse
     */
    public function getNurseReviews($nurseId): JsonResponse
    {
        $nurse = Nurse::findOrFail($nurseId);

        return $this->getReviews($nurse);
    }

    /**
     * Common method to get reviews
     */
    private function getReviews($reviewable): JsonResponse
    {
        $reviews = $reviewable->reviews()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'average_rating' => round($reviewable->averageRating() ?? 0, 2),
                'total_reviews' => $reviewable->totalReviews()
            ]
        ]);
    }

    /**
     * Check if user has reviewed
     */
    public function checkUserReview($type, $id): JsonResponse
    {
        $model = $type === 'doctor' ? Doctor::class : Nurse::class;
        $reviewable = $model::findOrFail($id);

        $review = Review::where('reviewable_id', $reviewable->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('user_id', Auth::id())
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'has_reviewed' => !is_null($review),
                'review' => $review
            ]
        ]);
    }
}
