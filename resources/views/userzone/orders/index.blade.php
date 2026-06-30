<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellingen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header row --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Overzicht bestellingen</h3>
                <a href="{{ route('orders.create') }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                    + Bestelling plaatsen
                </a>
            </div>

            {{-- Orders table --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $order->customer->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->product }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    € {{ number_format($order->totalPrice(), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    {{-- Status badge with color per status --}}
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
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                    Geen bestellingen gevonden. Plaats je eerste bestelling.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
