<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellingen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8">

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

            {{-- Dashboard shortcut filter (?status=..., optionally &mine=1) —
                 shows only the matching orders and keeps refreshing itself so
                 an order leaving the filter (e.g. picked up) disappears on
                 its own, no manual reload needed. --}}
            @if ($hasActiveFilter)
                <div class="mb-4 flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2 text-sm text-indigo-700">
                    <span>Filter actief — deze lijst ververst zichzelf automatisch.</span>
                    <a href="{{ route('orders.index') }}" class="font-medium hover:underline">Toon alles</a>
                </div>
                <script>
                    // Full reload, not a partial fetch — simplest way to keep
                    // this in sync with every other tab/user changing orders.
                    // Scroll position survives thanks to the layout's restore script.
                    setTimeout(() => window.location.reload(), 15000);
                </script>
            @endif

            @if ($isPureOrderpicker)
                {{-- Orderpicker gets a stripped-down page split in three:
                     still to prepare (top, the actual queue), then what he
                     finished himself, then colleagues' finished orders —
                     visible for context but read-only (see OrderController::
                     show()'s $canWorkOnPicking gate). --}}
                <div x-data="{ clientSearch: '' }">
                    <div class="mb-4">
                        <input type="text"
                               x-model="clientSearch"
                               placeholder="Zoek op #, klant, product, bedrag..."
                               class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    {{-- Nog te bereiden — the actual work queue --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Nog te bereiden</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-sortable-header column="id" label="#" />
                                <x-sortable-header column="customer" label="Klant" />
                                <x-sortable-header column="created_at" label="Besteldatum" />
                                <x-sortable-header column="paid" label="Betaling" />
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voorbereid door</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($toPrepare as $order)
                                @include('userzone.orders._row_orderpicker', ['order' => $order])
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        Geen bestellingen te bereiden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Mijn afgewerkte bestellingen — what he himself finished --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-3 mt-10">Mijn afgewerkte bestellingen</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-sortable-header column="id" label="#" />
                                <x-sortable-header column="customer" label="Klant" />
                                <x-sortable-header column="created_at" label="Besteldatum" />
                                <x-sortable-header column="paid" label="Betaling" />
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voorbereid door</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($myCompleted as $order)
                                @include('userzone.orders._row_orderpicker', ['order' => $order])
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        Nog geen afgewerkte bestellingen.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Bestellingen van collega's — view-only, no action buttons
                         render for these since their status is already
                         ready_for_pickup/received (see _row_orderpicker). --}}
                    @if ($colleagueCompleted->isNotEmpty())
                        <h3 class="text-lg font-medium text-gray-900 mb-3 mt-10">Bestellingen van collega's</h3>
                        <table class="min-w-full divide-y divide-gray-200 opacity-90">
                            <thead class="bg-gray-50">
                                <tr>
                                    <x-sortable-header column="id" label="#" />
                                    <x-sortable-header column="customer" label="Klant" />
                                    <x-sortable-header column="created_at" label="Besteldatum" />
                                    <x-sortable-header column="paid" label="Betaling" />
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voorbereid door</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($colleagueCompleted as $order)
                                    @include('userzone.orders._row_orderpicker', ['order' => $order])
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @else

            {{-- x-data lives here so the client search bar, the payment status
                 filter, and the payment confirmation alert can all be shared
                 across every row in both tables below (the main table + the
                 "Geannuleerde bestellingen" table). --}}
            <div x-data="{ paymentModalOpen: false, paymentForm: null, paymentMode: 'pay', paying: false, clientSearch: '', paymentFilter: 'all' }">

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
                       placeholder="Zoek op #, klant, product, bedrag..."
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <x-sortable-header column="id" label="#" />
                        <x-sortable-header column="customer" label="Klant" />
                        <x-sortable-header column="created_at" label="Besteldatum" />
                        <x-sortable-header column="status" label="Status" />
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statusdatum</th>
                        <x-sortable-header column="total" label="Totaal" />
                        <x-sortable-header column="paid" label="Betaling" />
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Print</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        @include('userzone.orders._row', ['order' => $order])
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400">
                                Geen bestellingen gevonden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

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
                    <table class="min-w-full divide-y divide-gray-200 opacity-90">
                        <thead class="bg-gray-50">
                            <tr>
                                <x-sortable-header column="id" label="#" />
                                <x-sortable-header column="customer" label="Klant" />
                                <x-sortable-header column="created_at" label="Besteldatum" />
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statusdatum</th>
                                <x-sortable-header column="total" label="Totaal" />
                                <x-sortable-header column="paid" label="Betaling" />
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Info</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Print</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($cancelledOrders as $order)
                                @include('userzone.orders._row', ['order' => $order])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-payment-confirm-modal />

            </div>
            @endif

        </div>
    </div>
</x-app-layout>
