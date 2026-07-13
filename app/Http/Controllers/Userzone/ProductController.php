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
        $products = Product::orderBy('name')->get();

        return view('userzone.products.index', compact('products'));
    }

    /**
     * Show the order history for a single product: every order that
     * included it, who ordered it, how many units, and when.
     *
     * Order items only store the product's name as a free-text string
     * (not a foreign key — see OrderItem), so historical lines still match
     * even if the product's price or article number changes later. This
     * also means a renamed product loses its old history, since the name
     * no longer matches — an accepted trade-off for how simple it keeps
     * the order form's product combobox.
     *
     * No status filter is applied on purpose: a cancelled or refused order
     * still shows up here (with its status visible) since it did happen,
     * even if it didn't end up going through.
     */
    public function history(Product $product)
    {
        $items = OrderItem::with(['order.customer'])
            ->where('product', $product->name)
            ->whereHas('order')
            ->get()
            ->sortByDesc(fn (OrderItem $item) => $item->order->created_at)
            ->values();

        $totalOrders = $items->count();
        $totalQuantity = $items->sum('quantity');

        return view('userzone.products.history', compact('product', 'items', 'totalOrders', 'totalQuantity'));
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
