<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestelgeschiedenis — {{ $product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8">

            {{-- Product summary — article number/price + the three headline numbers
                 this page exists for: how often it's been ordered, how many units
                 in total, and how much it has actually brought in (see
                 ProductController::history() for what counts as revenue). --}}
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Artikelnummer: {{ $product->article_number }}</p>
                        <p class="text-lg font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500">€ {{ number_format($product->price, 2, ',', '.') }} per stuk</p>
                    </div>
                    <div class="flex gap-6 text-center">
                        <div>
                            <p class="text-2xl font-semibold text-indigo-600">{{ $totalOrders }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Keer besteld</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-indigo-600">{{ $totalQuantity }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Stuks totaal</p>
                        </div>
                        <div>
                            <p class="text-2xl font-semibold text-emerald-600">€ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Omzet</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Order history table --}}
            <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prijs/stuk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $item->order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('orders.show', $item->order) }}"
                                       class="text-indigo-600 hover:text-indigo-900">
                                        #{{ $item->order->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $item->order->customer->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    € {{ number_format($item->unit_price, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <x-order-status-badge :status="$item->order->status" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Dit product is nog nooit besteld.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <div class="mt-4">
                <a href="{{ route('products.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                    ← Terug naar producten
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
