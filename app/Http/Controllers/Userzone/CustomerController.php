<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\SalesforceService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->get();

        return view('userzone.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('userzone.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol aangemaakt.');
    }

    /**
     * Search customers by name, email or company.
     * Used by the async combobox on the order creation form so we never
     * load thousands of customers into a single <select> at once (AJAX, JSON response).
     */
    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('company', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'email', 'company']);

        return response()->json($customers);
    }

    /**
     * List a customer's past orders (AJAX/JSON), for the "previous orders"
     * panel on the order creation form — see resources/views/layouts/
     * app.blade.php (customerSearch Alpine component). This replaced the
     * old per-row "Herbestellen" button on the orders list: reordering now
     * happens right where you're already picking a customer, instead of
     * hunting through the whole orders list for the right one to copy.
     *
     * No status filter — a cancelled/refused order still shows up here,
     * it's just history, same reasoning as ProductController::history().
     * Capped at the 10 most recent so this stays a quick glance, not a
     * second full order list.
     */
    public function orders(Customer $customer)
    {
        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'created_at' => $order->created_at->format('d/m/Y'),
                'items_summary' => $order->items->pluck('product')->join(', '),
                'total' => number_format($order->totalPrice(), 2, ',', '.'),
            ]);

        return response()->json($orders);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => function ($query) {
            $query->latest()->with('items');
        }]);

        return view('userzone.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('userzone.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol bijgewerkt.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol verwijderd.');
    }

    /**
     * Confirm a new customer to Salesforce with a single button — no terminal
     * needed, same idea as the order sync (ConsumeOrders). Reuses
     * SalesforceService::syncCustomer(), which creates or links the Account.
     */
    public function syncToSalesforce(Customer $customer)
    {
        $accountId = app(SalesforceService::class)->syncCustomer($customer);

        if (! $accountId) {
            return back()->with('error', 'Synchronisatie met Salesforce is mislukt. Probeer het later opnieuw.');
        }

        return back()->with('success', 'Klant succesvol gesynchroniseerd met Salesforce.');
    }
}
