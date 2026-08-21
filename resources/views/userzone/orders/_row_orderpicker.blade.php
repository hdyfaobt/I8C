{{-- Orderpicker row --}}
@php
    $orderSearch = \Illuminate\Support\Str::lower(implode(' ', [
        $order->id,
        $order->customer->name,
        $order->customer->company ?? '',
        $order->items->pluck('product')->implode(' '),
        number_format($order->totalPrice(), 2, '.', ''),
        number_format($order->totalPrice(), 2, ',', ''),
    ]));
@endphp
<tr x-show="clientSearch === '' || {{ \Illuminate\Support\Js::from($orderSearch) }}.includes(clientSearch.toLowerCase())">
    <td class="px-4 py-4 text-sm text-gray-500">
        #{{ $order->id }}
    </td>
    <td class="px-4 py-4 text-sm font-medium text-gray-900">
        {{ $order->customer->name }}
    </td>
    <td class="px-4 py-4 text-sm text-gray-500 whitespace-nowrap">
        {{ $order->created_at->format('d/m/Y H:i') }}
    </td>
    <td class="px-4 py-4 text-sm">
        @if ($order->paid)
            <span class="px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Betaald</span>
        @else
            <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">Niet betaald</span>
        @endif
    </td>
    {{-- Who's on it — lets an orderpicker spot a colleague's order at a glance --}}
    <td class="px-4 py-4 text-sm text-gray-500">
        @if ($order->prepared_by === auth()->id())
            <span class="text-purple-700 font-medium">Jij</span>
        @else
            {{ $order->preparedBy->name ?? '—' }}
        @endif
    </td>
    <td class="px-4 py-4 text-sm text-center">
        <a href="{{ route('orders.show', $order) }}"
           class="px-2.5 py-1 rounded text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
            Details
        </a>
    </td>
    <td class="px-4 py-4 text-sm text-right">
        @if (in_array($order->status, ['ready_for_pickup', 'received'], true))
            <span class="px-4 py-2 rounded text-sm font-medium bg-gray-100 text-gray-400">Voltooid</span>
        @else
            <form action="{{ route('orders.takeCharge', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded text-sm font-medium bg-purple-600 text-white hover:bg-purple-700 transition">
                    Overnemen
                </button>
            </form>
        @endif
    </td>
</tr>
