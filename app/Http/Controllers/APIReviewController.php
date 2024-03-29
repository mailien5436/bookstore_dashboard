<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class APIReviewController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'rating' => 'required',
        ], [
            'rating.required' => 'Bạn chưa chọn số sao.',
        ]);

        $book_id = $request->input('book_id', null);
        $combo_id = $request->input('combo_id', null);

        Review::updateOrCreate([
            'customer_id' => $request->customer_id,
            'book_id' => $book_id,
            'combo_id' => $combo_id,
        ], [
            'rating' => $request->rating,
            'content' => $request->content,
        ]);

        if ($book_id) {
            $book = Book::find($book_id);
            $averageRating = $book->reviews()->avg('rating');
            $book->update(['average_rating' => $averageRating]);
        } else if ($combo_id) {
            $combo = Combo::find($combo_id);
            $averageRating = $combo->reviews()->avg('rating');
            $combo->update(['average_rating' => $averageRating]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá sản phẩm thành công!'
        ]);
    }

    public function getReviewsByProductId(Request $request)
    {
        $perPage = $request->input('per_page', 4);

        $bookId = $request->input('book_id');
        $comboId = $request->input('combo_id');

        $reviews = [];
        $totalReviews = 0;
        $count1Star = 0;
        $count2Stars = 0;
        $count3Stars = 0;
        $count4Stars = 0;
        $count5Stars = 0;
        $averageRating = 0;

        if ($bookId) {
            $reviews = Review::with('customer')
                ->where('book_id', $bookId)
                ->latest()
                ->paginate($perPage);
            $allReviews = Review::where('book_id', $bookId)->get();
            $totalReviews = $allReviews->count();
            $count1Star = $allReviews->where('rating', 1)->count();
            $count2Stars = $allReviews->where('rating', 2)->count();
            $count3Stars = $allReviews->where('rating', 3)->count();
            $count4Stars = $allReviews->where('rating', 4)->count();
            $count5Stars = $allReviews->where('rating', 5)->count();
            $averageRating = Book::find($bookId)->average_rating;
        } else if ($comboId) {
            $reviews = Review::with('customer')
                ->where('combo_id', $comboId)
                ->latest()
                ->paginate($perPage);
            $allReviews = Review::where('combo_id', $comboId)->get();
            $totalReviews = $allReviews->count();
            $count1Star = $allReviews->where('rating', 1)->count();
            $count2Stars = $allReviews->where('rating', 2)->count();
            $count3Stars = $allReviews->where('rating', 3)->count();
            $count4Stars = $allReviews->where('rating', 4)->count();
            $count5Stars = $allReviews->where('rating', 5)->count();
            $averageRating = Combo::find($comboId)->average_rating;
        } else {
            $reviews = new LengthAwarePaginator([], 0, $perPage);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews->items(),
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
                'review_stats' => [
                    'count_1_star' => $count1Star,
                    'count_2_stars' => $count2Stars,
                    'count_3_stars' => $count3Stars,
                    'count_4_stars' => $count4Stars,
                    'count_5_stars' => $count5Stars,
                ],
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'total_pages' => $reviews->lastPage(),
            ],
        ]);
    }

    public function checkDelivered(Request $request)
    {
        $customer_id = $request->customer_id;
        $book_id = $request->book_id;
        $combo_id = $request->combo_id;

        $query = Order::where('customer_id', $customer_id);

        if ($book_id) {
            $query->whereHas('order_details', function ($q) use ($book_id) {
                $q->where('book_id', $book_id);
            });
        } else if ($combo_id) {
            $query->whereHas('order_details', function ($q) use ($combo_id) {
                $q->where('combo_id', $combo_id);
            });
        }

        $isDelivered = $query->exists();

        return response()->json([
            'success' => true,
            'is_delivered' => $isDelivered,
        ]);
    }
}
