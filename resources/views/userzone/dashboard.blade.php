<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome banner --}}
            <div class="rounded-2xl p-6 text-white" style="background-color: #1e2235;">
                <p class="text-sm font-medium" style="color: #da532c;">Welkom terug</p>
                <h1 class="text-2xl font-bold mt-1">{{ Auth::user()->name }}</h1>
                <p class="text-gray-400 text-sm mt-1">I8C Salesforce Integratie Platform</p>
            </div>

            {{-- Quick stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                {{-- Customers count --}}
                <a href="{{ route('customers.index') }}"
                   class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition block">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Klanten</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1">
                                {{ \App\Models\Customer::count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: #fef0eb;">
                            <svg class="w-6 h-6" style="color: #da532c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>

                {{-- Orders count --}}
                <a href="{{ route('orders.index') }}"
                   class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition block">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Bestellingen</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1">
                                {{ \App\Models\Order::count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: #fef0eb;">
                            <svg class="w-6 h-6" style="color: #da532c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                </a>

                {{-- Pending orders --}}
                <a href="{{ route('orders.index') }}"
                   class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition block">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">In afwachting</p>
                            <p class="text-3xl font-bold text-gray-800 mt-1">
                                {{ \App\Models\Order::where('status', 'pending')->count() }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background-color: #fef9e7;">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Quick actions --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Snelle acties</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customers.create') }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-medium text-white transition"
                       style="background-color: #da532c;">
                        + Klant toevoegen
                    </a>
                    <a href="{{ route('orders.create') }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-medium text-white transition"
                       style="background-color: #1e2235;">
                        + Bestelling plaatsen
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
