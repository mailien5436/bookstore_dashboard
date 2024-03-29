<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Combo;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APIBookController extends Controller
{
    public function home()
    {
        $newBooks = Book::latest()->take(4)->get();
        $bestsellers = Book::all()->where('total_quantity_sold_this_month', '>', 0)
            ->sortByDesc('total_quantity_sold_this_month')->take(4)->values();
        $combos = Combo::take(4)->get();
        $sliders = Slider::with('book')->where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'new_books' => $newBooks,
                'bestsellers' => $bestsellers,
                'combos' => $combos,
                'sliders' => $sliders,
            ],
        ]);
    }

    public function getNewBooks(Request $request)
    {
        $perPage = $request->input('per_page', 16);

        $newBooks = Book::latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'new_books' => $newBooks->items(),
                'per_page' => $newBooks->perPage(),
                'total' => $newBooks->total(),
                'total_pages' => $newBooks->lastPage(),
            ],
        ]);
    }

    public function getBestsellers(Request $request)
    {
        $perPage = $request->input('per_page', 16);
        $page = $request->input('page', 1);

        $books = Book::all();
        $sortedBooks = $books->where('total_quantity_sold_this_month', '>', 0)
            ->sortByDesc('total_quantity_sold_this_month');

        $total = $sortedBooks->count();
        $totalPages = ceil($total / $perPage);
        $bestsellers = $sortedBooks->skip(($page - 1) * $perPage)->take($perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'bestsellers' => $bestsellers,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    public function getCombos(Request $request)
    {
        $perPage = $request->input('per_page', 16);

        $combos = Combo::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'combos' => $combos->items(),
                'per_page' => $combos->perPage(),
                'total' => $combos->total(),
                'total_pages' => $combos->lastPage(),
            ],
        ]);
    }

    public function getProductBySlug($slug)
    {
        $book = Book::with(['authors', 'images', 'combos'])
            ->where('slug', $slug)
            ->first();
        $combo = Combo::where('slug', $slug)->first();
        $product = $book ? $book : $combo;

        if (empty($product)) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại!',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function getBooksByCategoryId($category_id)
    {
        $books = Book::with('images')->where('category_id', $category_id)->get();

        if (empty($books)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách trống!',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 16);
        $keyword = $request->input('keyword');

        $books = Book::with('images')
            ->where('slug', 'LIKE', '%' . $keyword . '%')
            ->paginate($perPage);

        $combos = Combo::where('slug', 'LIKE', '%' . $keyword . '%')
            ->paginate($perPage);

        $products = array_merge($books->items(), $combos->items());

        $totalBooks = Book::where('slug', 'LIKE', '%' . $keyword . '%')->count();
        $totalCombos = Combo::where('slug', 'LIKE', '%' . $keyword . '%')->count();
        $totalProducts = $totalBooks + $totalCombos;
        $total_pages = max($books->lastPage(), $combos->lastPage());

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'total' => $totalProducts,
                'per_page' => $perPage,
                'total_pages' => $total_pages,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $perPage = $request->input('per_page', 16);

        $query = Book::with('images');

        if ($request->has('category_id')) {
            $categoryIds = explode(',', $request->input('category_id'));
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->has('price_range')) {
            $priceRange = $request->input('price_range');
            switch ($priceRange) {
                case '1':
                    $query->where('price', '<=', 100000);
                    break;
                case '2':
                    $query->whereBetween('price', [100000, 300000]);
                    break;
                case '3':
                    $query->whereBetween('price', [200000, 500000]);
                    break;
                case '4':
                    $query->where('price', '>=', 500000);
                    break;
                default:
            }
        }

        if ($request->has('author_id')) {
            $authorIds = explode(',', $request->input('author_id'));
            $query->whereHas('authors', function ($q) use ($authorIds) {
                $q->whereIn('author_id', $authorIds);
            });
        }

        $sort = $request->input('sort', 1);

        if ($sort == 1) {
            $query->latest();
        } else if ($sort == 2) {
            $query->select(['books.*', DB::raw('(SELECT SUM(quantity) FROM order_details WHERE order_details.book_id = books.id AND EXISTS (SELECT * FROM orders WHERE orders.id = order_details.order_id AND orders.status = 4 AND MONTH(orders.created_at) = MONTH(NOW()))) as total_quantity_sold_this_month')])
                ->orderByDesc('total_quantity_sold_this_month');
        }

        $books = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'books' => $books->items(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
                'total_pages' => $books->lastPage(),
            ],
        ]);
    }
}
