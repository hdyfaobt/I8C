<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display the list of all customers.
     */
    public function index()
    {
        // Retrieve all customers, newest first
        $customers = Customer::latest()->get();

        return view('userzone.customers.index', compact('customers'));
    }

    /**
     * Show the form to create a new customer.
     */
    public function create()
    {
        return view('userzone.customers.create');
    }

    /**
     * Validate and store a new customer in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:customers,email',
            'phone'   => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol aangemaakt.');
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
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:customers,email,' . $customer->id,
            'phone'   => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol bijgewerkt.');
    }

    /**
     * Delete a customer from the database.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Klant succesvol verwijderd.');
    }
}
