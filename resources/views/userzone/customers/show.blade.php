<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $customer->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success message --}}
            @if (session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Customer details card --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-4">

                {{-- Salesforce sync status --}}
                <div>
                    @if ($customer->salesforce_id)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">✓ Gesynchroniseerd met Salesforce</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">⏳ In afwachting van synchronisatie</span>
                    @endif
                </div>

                <hr>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Naam</p>
                        <p class="font-medium">{{ $customer->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">E-mail</p>
                        <p class="font-medium">{{ $customer->email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Telefoon</p>
                        <p class="font-medium">{{ $customer->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Bedrijf</p>
                        <p class="font-medium">{{ $customer->company ?? '—' }}</p>
                    </div>
                    @if ($customer->address)
                    <div class="col-span-2">
                        <p class="text-gray-500">Adres</p>
                        <p class="font-medium">{{ $customer->address }}</p>
                    </div>
                    @endif
                    @if ($customer->salesforce_id)
                    <div class="col-span-2">
                        <p class="text-gray-500">Salesforce ID</p>
                        <p class="font-mono text-xs">{{ $customer->salesforce_id }}</p>
                    </div>
                    @endif
                </div>

                <hr>

                <div class="flex items-center justify-between">
                    <a href="{{ route('customers.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                        ← Terug naar klanten
                    </a>
                    <a href="{{ route('customers.edit', $customer) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                        Bewerken
                    </a>
                </div>
            </div>

            {{-- Order history card --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Bestelgeschiedenis</h3>
                    <a href="{{ route('orders.create') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-sm">
                        + Bestelling plaatsen
                    </a>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($customer->orders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $order->product }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    € {{ number_format($order->totalPrice(), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($order->status === 'sent')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Verzonden</span>
                                    @elseif ($order->status === 'failed')
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Mislukt</span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">In afwachting</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Nog geen bestellingen voor deze klant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
