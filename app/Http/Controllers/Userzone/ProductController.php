<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display the product catalog.
     * Viewing is open to receptionist/admin/manager (see routes/web.php) —
     * anyone who might need to look up a price or article number while
     * placing an order.
     */
    public function index()
    {
        $sortable = ['id', 'article_number', 'name', 'price'];
        $sort = request('sort');
        $direction = request('direction') === 'desc' ? 'desc' : 'asc';

        $products = Product::orderBy(in_array($sort, $sortable, true) ? $sort : 'name', $direction)->get();

        return view('userzone.products.index', compact('products'));
    }

    // Never a real sale, excluded from revenue.
    private const NON_REVENUE_STATUSES = ['cancelled', 'refused', 'failed'];

    // Product rename fallback, see add_product_id_to_order_items_table.
    public function history(Product $product)
    {
        $items = OrderItem::with(['order.customer'])
            ->where(function ($builder) use ($product) {
                $builder->where('product_id', $product->id)
                    ->orWhere(function ($builder) use ($product) {
                        $builder->whereNull('product_id')->where('product', $product->name);
                    });
            })
            ->whereHas('order')
            ->get()
            // Out of stock = never actually delivered, so leave it out entirely.
            ->reject(fn (OrderItem $item) => $item->isOutOfStock())
            ->sortByDesc(fn (OrderItem $item) => $item->order->created_at)
            ->values();

        $totalOrders = $items->count();
        $totalQuantity = $items->sum('quantity');

        $totalRevenue = $items
            ->reject(fn (OrderItem $item) => in_array($item->order->status, self::NON_REVENUE_STATUSES, true))
            ->sum(fn (OrderItem $item) => $item->lineTotal());

        return view('userzone.products.history', compact('product', 'items', 'totalOrders', 'totalQuantity', 'totalRevenue'));
    }

    /**
     * Search products by name or article number.
     * Used by the async product combobox on the order creation form so the
     * user can search "salami", "chèvre", ... instead of scrolling a huge
     * list (same pattern as CustomerController::search(), AJAX/JSON).
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('article_number', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'article_number', 'name', 'price']);

        return response()->json($products);
    }

    public function create()
    {
        return view('userzone.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'article_number' => 'required|string|max:50|unique:products,article_number',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product succesvol toegevoegd.');
    }

    public function edit(Product $product)
    {
        return view('userzone.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'article_number' => ['required', 'string', 'max:50', Rule::unique('products', 'article_number')->ignore($product->id)],
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product succesvol bijgewerkt.');
    }

    /**
     * Delete a product from the catalog.
     * Note: this only removes it from the catalog — order_items keep their
     * own product/price snapshot, so past orders are unaffected.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product succesvol verwijderd.');
    }
}
