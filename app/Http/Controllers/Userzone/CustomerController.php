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
     * Display the details of a single customer, including their order history.
     */
    public function show(Customer $customer)
    {
        // Eager-load the customer's orders, newest first
        $customer->load(['orders' => function ($query) {
            $query->latest();
        }]);

        return view('userzone.customers.show', compact('customer'));
    }

    /**
     * Show the form to edit an existing customer.
     */
    public function edit(Customer $customer)
    {
        // Laravel auto-resolves $customer via route model binding
        return view('userzone.customers.edit', compact('customer'));
    }

    /**
     * Validate and update an existing customer.
     */
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
}
