<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellingen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Success message — shared by both views below --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if ($isPureOrderpicker)
                {{-- Orderpicker gets a deliberately stripped-down page: only what
                     they need (klant, datum, details, overnemen). No status/
                     totaal/betaling/print — an order only ever shows up here once
                     it's already accepted, so none of that is their concern. --}}
                <div x-data="{ clientSearch: '' }">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Overzicht bestellingen</h3>

                    <div class="mb-4">
                        <input type="text"
                               x-model="clientSearch"
                               placeholder="Zoek op klant..."
                               class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Besteldatum</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($orders as $order)
                                    @include('userzone.orders._row_orderpicker', ['order' => $order])
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                            Geen bestellingen gevonden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else

            {{-- x-data lives here so the client search bar, the payment status
                 filter, and the payment confirmation alert can all be shared
                 across every row in both tables below (the main table + the
                 "Geannuleerde bestellingen" table). --}}
            <div x-data="{ paymentModalOpen: false, paymentForm: null, paymentMode: 'pay', clientSearch: '', paymentFilter: 'all' }">

            {{-- Header row — only receptionist/admin/manager can place new orders --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Overzicht bestellingen</h3>
                @hasanyrole('receptionist|admin|manager')
                    <a href="{{ route('orders.create') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        + Bestelling plaatsen
                    </a>
                @endhasanyrole
            </div>

            {{-- Client search bar + payment status filter — both filter the rows
                 in both tables below, purely client-side (no page reload).
                 Typing a client name won't match on "geannuleerd" since this
                 searches the customer, not the status — a cancelled order for
                 that customer will simply show up with its "Geannuleerd" badge
                 in the section below. --}}
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <input type="text"
                       x-model="clientSearch"
                       placeholder="Zoek op klant..."
                       class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

                <select x-model="paymentFilter"
                        class="rounded border-2 border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="all">Alle betalingen</option>
                    <option value="unpaid">Enkel niet betaald</option>
                    <option value="paid">Enkel betaald</option>
                </select>
            </div>

            {{-- Orders table — exactly 9 columns:
                 #, Klant, Besteldatum, Status, Statusdatum, Totaal, Betaling, Actie, Print.
                 The wrapper uses overflow-x-auto (not overflow-hidden) and the Actie
                 column uses flex-wrap instead of whitespace-nowrap, so buttons stack
                 onto a second line instead of getting clipped off the right edge.
                 Cancelled orders are deliberately left out of this table — see the
                 separate "Geannuleerde bestellingen" table further down. --}}
            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Besteldatum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statusdatum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal</th>
                            {{-- Independent from Status on purpose — see OrderController::togglePaid() --}}
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Print</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            @include('userzone.orders._row', ['order' => $order])
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-gray-400">
                                    Geen bestellingen gevonden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Geannuleerde bestellingen — a cancelled order drops out of the main
                 table above but isn't hidden entirely: it lands here, still visible
                 and its Details link still works exactly the same way. Reordering it
                 is done from the order creation form now (pick the customer there and
                 reuse one of their past orders) rather than a button on this row.
                 Only rendered when there's at least one, so it doesn't clutter the
                 page with an empty section. --}}
            @if ($cancelledOrders->isNotEmpty())
                <div class="mt-8">
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                        Geannuleerde bestellingen
                    </h3>
                    <div class="bg-white shadow-sm rounded-lg overflow-x-auto opacity-90">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Besteldatum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statusdatum</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Print</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($cancelledOrders as $order)
                                    @include('userzone.orders._row', ['order' => $order])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Custom payment confirmation alert — replaces the native browser
                 confirm() popup with a translucent, colored alert that matches
                 the site's look: green while confirming a payment (asks for the
                 payment method too), orange while undoing one. Shared by every
                 row's "Betaling" button above, in both tables. --}}
            <div x-show="paymentModalOpen"
                 x-cloak
                 style="display: none;"
                 @click.self="paymentModalOpen = false"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 px-4">
                <div x-show="paymentModalOpen"
                     x-transition
                     class="w-full max-w-sm rounded-xl border p-6 shadow-lg backdrop-blur-sm"
                     :class="paymentMode === 'pay'
                        ? 'bg-green-100/80 border-green-300'
                        : 'bg-orange-100/80 border-orange-300'">

                    {{-- Marking as paid — pick a payment method first --}}
                    <template x-if="paymentMode === 'pay'">
                        <div>
                            <p class="text-sm font-medium text-green-800">
                                Bevestig: hoe werd deze bestelling betaald?
                            </p>
                            <div class="mt-4 flex flex-col gap-2">
                                <button type="button"
                                        @click="paymentForm.querySelector('[name=payment_method]').value = 'bank_transfer'; paymentForm.submit(); paymentModalOpen = false"
                                        class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition">
                                    Overschrijving / Bancontact
                                </button>
                                <button type="button"
                                        @click="paymentForm.querySelector('[name=payment_method]').value = 'cash'; paymentForm.submit(); paymentModalOpen = false"
                                        class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition">
                                    Cash
                                </button>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button"
                                        @click="paymentModalOpen = false"
                                        class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                                    Annuleren
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Undoing a payment — manager/admin only, plain confirm --}}
                    <template x-if="paymentMode === 'revert'">
                        <div>
                            <p class="text-sm font-medium text-orange-800">
                                Bevestig: deze bestelling markeren als NIET betaald?
                            </p>
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button"
                                        @click="paymentModalOpen = false"
                                        class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                                    Annuleren
                                </button>
                                <button type="button"
                                        @click="paymentForm.submit(); paymentModalOpen = false"
                                        class="px-3 py-1.5 rounded text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 transition">
                                    Bevestigen
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            </div>
            @endif

        </div>
    </div>
</x-app-layout>
