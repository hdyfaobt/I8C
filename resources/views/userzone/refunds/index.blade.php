<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Terugbetalingen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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
            <div class="bg-white shadow-sm rounded-lg overflow-x-auto mb-10">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900">Openstaande terugbetalingen</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedrag</th>
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

            {{-- Already refunded — kept for reference/audit, not just deleted --}}
            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900">Al terugbetaald</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestelling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betaling</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedrag</th>
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
