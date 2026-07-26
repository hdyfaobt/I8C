<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Terugbetalingen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Summary — total still owed to customers for out-of-stock items --}}
            <div class="mb-6 p-4 rounded-lg border {{ $pending->isEmpty() ? 'bg-gray-50 border-gray-200' : 'bg-orange-50 border-orange-200' }}">
                <p class="text-sm text-gray-500">Nog terug te betalen aan klanten</p>
                <p class="text-2xl font-semibold {{ $pending->isEmpty() ? 'text-gray-400' : 'text-orange-700' }}">
                    € {{ number_format($pendingTotal, 2, ',', '.') }}
                </p>
            </div>

            {{-- Pending refunds — items reported out of stock, not yet paid back --}}
            <div class="mb-10">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Openstaande terugbetalingen</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="customer" label="Klant" />
                            <x-sortable-header column="order" label="Bestelling" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                            <x-sortable-header column="product" label="Product" />
                            <x-sortable-header column="quantity" label="Aantal" />
                            <x-sortable-header column="amount" label="Bedrag" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sinds</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($pending as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $item->order->customer->name }}
                                    <span class="text-gray-400 font-mono text-xs">({{ $item->order->customer->customerNumber() }})</span>
                                    @if ($item->order->customer->company)
                                        <span class="text-gray-400">({{ $item->order->customer->company }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('orders.show', $item->order) }}" class="text-indigo-600 hover:text-indigo-800">
                                        #{{ $item->order_id }}
                                    </a>
                                </td>
                                {{-- Whether this order was actually paid decides whether there's
                                     real money to give back, see OrderItem::refundAmount() --}}
                                <td class="px-6 py-4 text-sm">
                                    @if ($item->order->paid)
                                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Betaald</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">Niet betaald</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $item->product }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($item->order->paid)
                                        <span class="font-medium text-orange-700">€ {{ number_format($item->refundAmount(), 2, ',', '.') }}</span>
                                    @else
                                        <span class="font-medium text-gray-400">€ 0,00</span>
                                        <p class="text-[11px] text-gray-400">niet betaald — schuld al verminderd</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $item->out_of_stock_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <form action="{{ route('refunds.markRefunded', $item) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded text-xs font-medium bg-orange-100 text-orange-700 hover:bg-orange-200 transition">
                                            {{ $item->order->paid ? 'Terugbetaald' : 'Verwerkt' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                    Geen openstaande terugbetalingen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Whole orders — refused/failed after being paid, full amount owed back --}}
            <div class="mb-10">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Geweigerde / mislukte bestellingen (volledig betaald)</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedrag</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($pendingOrders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $order->customer->name }}
                                    @if ($order->customer->company)
                                        <span class="text-gray-400">({{ $order->customer->company }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800">
                                        #{{ $order->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2.5 py-1 rounded text-xs font-medium bg-red-100 text-red-700">
                                        {{ $order->status === 'refused' ? 'Geweigerd' : 'Mislukt' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-orange-700">
                                    € {{ number_format($order->totalPrice(), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <form action="{{ route('refunds.markOrderRefunded', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded text-xs font-medium bg-orange-100 text-orange-700 hover:bg-orange-200 transition">
                                            Terugbetaald
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Geen openstaande terugbetalingen voor bestellingen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($doneOrders->isNotEmpty())
                <div class="mb-10">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Terugbetaalde bestellingen</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedrag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terugbetaald op</th>
                                @hasanyrole('admin|manager')
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                                @endhasanyrole
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($doneOrders as $order)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $order->customer->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800">
                                            #{{ $order->id }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-green-700">
                                        € {{ number_format($order->totalPrice(), 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $order->refunded_at->format('d/m/Y') }}
                                    </td>
                                    @hasanyrole('admin|manager')
                                        <td class="px-6 py-4 text-sm text-right">
                                            <form action="{{ route('refunds.markOrderRefunded', $order) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                                    Herstel
                                                </button>
                                            </form>
                                        </td>
                                    @endhasanyrole
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Already refunded — kept for reference/audit, not just deleted --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-3">Al terugbetaald</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="customer" label="Klant" />
                            <x-sortable-header column="order" label="Bestelling" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                            <x-sortable-header column="product" label="Product" />
                            <x-sortable-header column="quantity" label="Aantal" />
                            <x-sortable-header column="amount" label="Bedrag" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terugbetaald op</th>
                            {{-- Undoing an already-processed refund is a manager/admin call, same rule
                                 as reverting a payment (see OrderController::togglePaid()) --}}
                            @hasanyrole('admin|manager')
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actie</th>
                            @endhasanyrole
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($done as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $item->order->customer->name }}
                                    <span class="text-gray-400 font-mono text-xs">({{ $item->order->customer->customerNumber() }})</span>
                                    @if ($item->order->customer->company)
                                        <span class="text-gray-400">({{ $item->order->customer->company }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('orders.show', $item->order) }}" class="text-indigo-600 hover:text-indigo-800">
                                        #{{ $item->order_id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($item->order->paid)
                                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Betaald</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">Niet betaald</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $item->product }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($item->order->paid)
                                        <span class="font-medium text-green-700">€ {{ number_format($item->refundAmount(), 2, ',', '.') }}</span>
                                    @else
                                        <span class="font-medium text-gray-400">€ 0,00</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $item->refunded_at->format('d/m/Y') }}
                                </td>
                                @hasanyrole('admin|manager')
                                    <td class="px-6 py-4 text-sm text-right">
                                        <form action="{{ route('refunds.markRefunded', $item) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                                Herstel
                                            </button>
                                        </form>
                                    </td>
                                @endhasanyrole
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                    Nog geen terugbetalingen verwerkt.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
