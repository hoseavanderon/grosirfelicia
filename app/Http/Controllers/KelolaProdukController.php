<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;    
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\DetailProduct;

class KelolaProdukController extends Controller
{
    public function index()
    {
        $totalProduct = Product::where('user_id', Auth::id())->count();
        $totalCategory = Category::count(); // Kalau kategori tidak terkait user, bisa tetap seperti ini

        $today = Carbon::today();
        $oneMonthLater = $today->copy()->addMonth();

        $expiringSoon = Product::where('user_id', Auth::id())
            ->whereHas('detailProducts', function ($query) use ($today, $oneMonthLater) {
                $query->whereBetween('expired', [$today, $oneMonthLater])
                    ->where('stok', '>', 0);
            })
            ->with(['detailProducts' => function ($query) use ($today, $oneMonthLater) {
                $query->whereBetween('expired', [$today, $oneMonthLater])
                    ->where('stok', '>', 0);
            }])
            ->get();

        $lowStock = Product::where('user_id', Auth::id())
            ->whereHas('detailProducts', function ($query) {
                $query->where('stok', '<', 30)
                    ->where('stok', '>', 0);
            })
            ->with(['detailProducts' => function ($query) {
                $query->where('stok', '<', 30)
                    ->where('stok', '>', 0);
            }])
            ->get();

        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();

        $bestSellers = DB::table('detail_transactions as dt')
            ->join('detail_products as dp', 'dt.detail_product_id', '=', 'dp.id')
            ->join('products as p', 'dp.product_id', '=', 'p.id')
            ->select('dt.detail_product_id', DB::raw('SUM(dt.pcs) as total_terjual'))
            ->whereBetween('dt.created_at', [$startOfYear, $endOfYear])
            ->whereNull('dt.deleted_at')
            ->where('p.user_id', Auth::id())
            ->groupBy('dt.detail_product_id')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        $bestSellingProducts = Product::whereIn('id', $bestSellers->pluck('detail_product_id'))
            ->where('user_id', Auth::id())
            ->get()
            ->keyBy('id');

        return view('pages.kelola', compact(
            'totalProduct',
            'totalCategory',
            'expiringSoon',
            'lowStock',
            'bestSellers',
            'bestSellingProducts'
        ));
    }


    public function produk(Request $request)
    {
        $expired = $request->get('expired'); 

        $products = Product::where('user_id', Auth::id())
            ->whereHas('detailProducts', function ($query) {
                $query->where('stok', '>', 0);
            })
            ->with(['detailProducts' => function ($query) {
                $query->where('stok', '>', 0);
            }])
            ->get();

        return view('pages.produk', compact('products'));
    }

    public function kategori()
    {
        $categories = Category::all();

        return view('pages.kategori', compact('categories'));
    }

    public function create()
    {
        $categories = Category::all(); 
        $brands = Brand::all(); 

        return view('pages.product.create', compact('categories', 'brands'));
    }

    public function search(Request $request)
    {
        $keyword = $request->get('cari');

        $query = Product::with('detailProducts')
            ->where('user_id', Auth::id());

        if ($keyword) {
            $query->where('nama_produk', 'LIKE', "%$keyword%");
        }

        $products = $query->get();

        return response()->json([
            'html' => view('partials.product-list', compact('products'))->render()
        ]);
    }

    public function load(Request $request)
    {
        $keyword = $request->get('cari');
        $perPage = 5;

        $query = Product::where('user_id', Auth::id())
            ->whereHas('detailProducts', function ($query) {
                $query->where('stok', '>', 0);
            })
            ->with(['detailProducts' => function ($query) {
                $query->where('stok', '>', 0);
            }]);

        if ($keyword) {
            $query->where('nama_produk', 'LIKE', "%$keyword%");
        }

        $products = $query->orderBy('created_at', 'ASC')->paginate($perPage);

        return response()->json([
            'html' => view('partials.product-list', ['products' => $products])->render(),
            'next_page_url' => $products->nextPageUrl()
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'nama_produk' => 'required|string|max:255',
            'harga_jual' => 'required|numeric',
        ]);

        Product::create([
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'nama_produk' => $request->nama_produk,
            'harga_jual' => $request->harga_jual,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('kelola.produk.daftar')->with('success', 'Produk berhasil ditambahkan!');
    }

    public function categoryCreate(){
        return view('pages.category.create');
    }  
    
    public function categoryStore(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
        ]);
    
        Category::create([
            'category' => $request->category,
        ]);
    
        return redirect()->route('kelola.produk.kategori')->with('success', 'Kategori berhasil ditambahkan!');
    }
}
