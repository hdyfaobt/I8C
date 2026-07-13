<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'i8c') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Tailwind CSS & Alpine.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js"></script>

    <script>
        // Customer search combobox — used on the "create order" form.
        // Registered on the "alpine:init" event so it's ready before Alpine
        // scans the DOM (required since Alpine is loaded via CDN here, not
        // bundled through Vite — resources/js/app.js is not loaded on this layout).
        document.addEventListener('alpine:init', () => {
            /**
             * @param {object|null} selectedCustomer Pre-selected customer (e.g. after a validation error redisplay).
             * @param {string} searchUrl The "customers.search" route URL.
             * @param {string} ordersUrlTemplate The "customers.orders" route URL, with the
             *   customer id replaced by the literal placeholder "__ID__" — see create.blade.php,
             *   which builds this with route('customers.orders', ['customer' => '__ID__']).
             */
            Alpine.data('customerSearch', (selectedCustomer = null, searchUrl = '/customers/search', ordersUrlTemplate = '/customers/__ID__/orders') => ({
                query: selectedCustomer ? `${selectedCustomer.name} (${selectedCustomer.company ?? selectedCustomer.email})` : '',
                selectedId: selectedCustomer ? selectedCustomer.id : '',
                results: [],
                open: false,
                loading: false,

                // The selected customer's past orders — replaces the old
                // per-row "Herbestellen" button on the orders list: reordering
                // now happens right here, while a receptionist is already
                // picking the customer for a new order.
                pastOrders: [],
                loadingOrders: false,

                // If a customer was already selected when this component
                // mounted (redisplaying the form after a validation error),
                // load their past orders immediately too — not just after a
                // fresh manual selection.
                init() {
                    if (this.selectedId) {
                        this.loadPastOrders(this.selectedId);
                    }
                },

                // Called when the input gains focus. If nothing has been searched yet,
                // load a default list (first 15 customers, alphabetical) so there's
                // always something to pick from — not just after typing.
                onFocus() {
                    if (this.results.length > 0) {
                        this.open = true;
                    } else {
                        this.search();
                    }
                },

                // Fetch customers matching the current query from the server.
                // An empty query still hits the backend, which returns the first 15
                // customers (alphabetical) — that's the "default list" shown on focus.
                async search() {
                    // Any manual edit of the text invalidates the previous selection.
                    this.selectedId = '';
                    this.pastOrders = [];
                    this.loading = true;
                    this.open = true;

                    try {
                        const url = new URL(searchUrl, window.location.origin);
                        url.searchParams.set('q', this.query);

                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                        });

                        this.results = await response.json();
                    } finally {
                        this.loading = false;
                    }
                },

                // Select a customer from the results list.
                select(customer) {
                    this.selectedId = customer.id;
                    this.query = `${customer.name} (${customer.company ?? customer.email})`;
                    this.results = [];
                    this.open = false;
                    this.loadPastOrders(customer.id);
                },

                // Fetch a customer's past orders (see CustomerController::orders()).
                async loadPastOrders(customerId) {
                    this.loadingOrders = true;
                    this.pastOrders = [];

                    try {
                        const url = ordersUrlTemplate.replace('__ID__', customerId);
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                        });

                        this.pastOrders = await response.json();
                    } finally {
                        this.loadingOrders = false;
                    }
                },
            }));
        });
    </script>

    <style>
        /* I8C brand colors */
        :root {
            --i8c-orange: #da532c;
            --i8c-orange-dark: #b8421f;
            --i8c-navy: #1e2235;
            --i8c-navy-light: #2a3050;
        }
    </style>
</head>
<body class="font-sans antialiased" style="background-color: #f1f3f7;">

    @include('layouts.app_navigation')

    <!-- Page Heading -->
    @isset($header)
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>

</body>
</html>
