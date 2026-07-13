{{-- Orderpicker row --}}
<tr x-show="clientSearch === '' || {{ \Illuminate\Support\Js::from(\Illuminate\Support\Str::lower($order->customer->name.' '.($order->customer->company ?? ''))) }}.includes(clientSearch.toLowerCase())">
    <td class="px-6 py-4 text-sm text-gray-500">
        #{{ $order->id }}
    </td>
    <td class="px-6 py-4 text-sm font-medium text-gray-900">
        {{ $order->customer->name }}
    </td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
        {{ $order->created_at->format('d/m/Y H:i') }}
    </td>
    <td class="px-6 py-4 text-sm text-center">
        <a href="{{ route('orders.show', $order) }}"
           class="px-2.5 py-1 rounded text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
            Details
        </a>
    </td>
    <td class="px-6 py-4 text-sm text-right">
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
