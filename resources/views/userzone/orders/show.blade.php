<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestelling #{{ $order->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- x-data lives here so the payment confirmation alert below can be
             triggered by the "Betalen"/"Betaald" button further down. --}}
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8"
             x-data="{ paymentModalOpen: false, paymentForm: null, paymentMode: 'pay' }">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-4">

                {{-- Status badge + Betaald badge — two independent facts shown side by side.
                     "Betaald" is NOT part of the status lifecycle, see OrderController::togglePaid(). --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if ($order->status === 'awaiting_review')
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">🕓 Wacht op validatie</span>
                    @elseif ($order->status === 'sent')
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">📤 Naar de orderpicker</span>
                    @elseif ($order->status === 'failed')
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">✗ Verzending mislukt</span>
                    @elseif ($order->status === 'refused')
                        <span class="px-3 py-1 bg-red-200 text-red-800 rounded-full text-sm">⊗ Geweigerd</span>
                    @elseif ($order->status === 'cancelled')
                        <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm">⊘ Geannuleerd</span>
                    @elseif ($order->status === 'ready_for_pickup')
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm">📦 Klaar om op te halen</span>
                    @elseif ($order->status === 'received')
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm">✓ Ontvangen door klant</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">⏳ In afwachting van verwerking</span>
                    @endif

                    @if ($order->paid)
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm">
                            ✓ Betaald
                            @if ($order->payment_method)
                                ({{ $order->payment_method === 'cash' ? 'Cash' : 'Overschrijving/Bancontact' }})
                            @endif
                        </span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm">Niet betaald</span>
                    @endif
                </div>

                <hr>

                {{-- Order details --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Klant</p>
                        <p class="font-medium">{{ $order->customer->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Bedrijf</p>
                        <p class="font-medium">{{ $order->customer->company ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Product</p>
                        <p class="font-medium">{{ $order->product }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Aantal</p>
                        <p class="font-medium">{{ $order->quantity }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Eenheidsprijs</p>
                        <p class="font-medium">€ {{ number_format($order->unit_price, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Totaal</p>
                        <p class="font-medium text-indigo-600">€ {{ number_format($order->totalPrice(), 2, ',', '.') }}</p>
                    </div>
                    @if ($order->salesforce_id)
                    <div class="col-span-2">
                        <p class="text-gray-500">Salesforce ID</p>
                        <p class="font-mono text-xs">{{ $order->salesforce_id }}</p>
                    </div>
                    @endif
                    @if ($order->notes)
                    <div class="col-span-2">
                        <p class="text-gray-500">Opmerkingen</p>
                        <p>{{ $order->notes }}</p>
                    </div>
                    @endif
                </div>

                <hr>

                <a href="{{ route('orders.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                    ← Terug naar bestellingen
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
