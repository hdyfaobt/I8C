{{--
    Simplified order row for a pure orderpicker — see index.blade.php.
    Only what they actually need: the order #, who it's for, when it came
    in, a way to open the full details, and the "Overnemen" claim button.
    No status/totaal/betaling here — everything shown to them is already
    accepted (and payment isn't their concern at all), so that would just
    be noise. The detail page (orders/show.blade.php) still shows the full
    picture if they want it.
--}}
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
    <td class="px-6 py-4 text-sm">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('orders.show', $order) }}"
               class="px-2.5 py-1 rounded text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                Details
            </a>

            {{-- Overnemen: claims the order and jumps straight to its detail
                 page, where each item gets picked (or reported out of stock)
                 before the order can be marked ready — see
                 OrderController::takeCharge(). --}}
            @if (in_array($order->status, ['ready_for_pickup', 'received'], true))
                <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-400">Voltooid</span>
            @else
                <form action="{{ route('orders.takeCharge', $order) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-2.5 py-1 rounded text-xs font-medium bg-purple-600 text-white hover:bg-purple-700 transition">
                        Overnemen
                    </button>
                </form>
            @endif
        </div>
    </td>
</tr>
