{{--
    Single order row — shared by both tables in orders/index.blade.php
    (the main "active" table and the "Geannuleerde bestellingen" table
    below it), so the exact same 9 columns/logic render in both places.

    Expects a single $order variable, and relies on the Alpine x-data
    declared on the page (paymentModalOpen/paymentForm/paymentMode for the
    payment alert, clientSearch for the client search bar, paymentFilter for
    the "toon enkel niet betaald" filter) — all defined once in
    index.blade.php and shared across every included row.
--}}
<tr x-show="(clientSearch === '' || {{ \Illuminate\Support\Js::from(\Illuminate\Support\Str::lower($order->customer->name.' '.($order->customer->company ?? ''))) }}.includes(clientSearch.toLowerCase())) && (paymentFilter === 'all' || (paymentFilter === 'paid') === {{ $order->paid ? 'true' : 'false' }})">
    <td class="px-6 py-4 text-sm text-gray-500">{{ $order->id }}</td>

    <td class="px-6 py-4 text-sm font-medium text-gray-900">
        {{ $order->customer->name }}
    </td>

    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
        {{ $order->created_at->format('d/m/Y H:i') }}
    </td>

    {{-- Status — a distinct, logically-matched color per status --}}
    <td class="px-6 py-4 text-sm">
        @if ($order->status === 'awaiting_review')
            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Wacht op validatie</span>
        @elseif ($order->status === 'pending')
            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">In afwachting</span>
        @elseif ($order->status === 'sent')
            <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs">Naar de orderpicker</span>
        @elseif ($order->status === 'failed')
            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Mislukt</span>
        @elseif ($order->status === 'refused')
            <span class="px-2 py-1 bg-red-200 text-red-800 rounded text-xs">Geweigerd</span>
        @elseif ($order->status === 'cancelled')
            <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">Geannuleerd</span>
        @elseif ($order->status === 'ready_for_pickup')
            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">Klaar om op te halen</span>
        @elseif ($order->status === 'received')
            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Ontvangen door klant</span>
        @endif
    </td>

    {{-- Statusdatum — the date matching whichever status is shown above
         (e.g. the pickup date once ready_for_pickup, the received date once
         received, ...) --}}
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
        @if ($order->status === 'awaiting_review')
            {{ $order->created_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'pending' && $order->accepted_at)
            {{ $order->accepted_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'sent' && $order->sent_at)
            {{ $order->sent_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'failed' && $order->failed_at)
            {{ $order->failed_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'refused' && $order->refused_at)
            {{ $order->refused_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'cancelled' && $order->cancelled_at)
            {{ $order->cancelled_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'ready_for_pickup' && $order->ready_at)
            {{ $order->ready_at->format('d/m/Y H:i') }}
        @elseif ($order->status === 'received' && $order->received_at)
            {{ $order->received_at->format('d/m/Y H:i') }}
        @else
            —
        @endif
    </td>

    <td class="px-6 py-4 text-sm text-gray-500">
        € {{ number_format($order->totalPrice(), 2, ',', '.') }}
    </td>

    {{-- Betaling — a single button whose color/label IS the status:
         orange "Betalen" when unpaid, green "Betaald" once paid.
         Cancelled orders can never be paid — there's nothing left to
         collect payment for once an order is dead, so they get a plain
         "n.v.t." (not applicable) badge instead, and OrderController::
         togglePaid() rejects the request server-side too. --}}
    <td class="px-6 py-4 text-sm">
        <div class="flex flex-col items-start gap-0.5">
            @if ($order->status === 'cancelled')
                <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 text-gray-400">n.v.t.</span>
            @elseif ($order->paid)
                @hasanyrole('admin|manager')
                    <form action="{{ route('orders.togglePaid', $order) }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method">
                        <button type="button"
                                @click="paymentForm = $el.closest('form'); paymentMode = 'revert'; paymentModalOpen = true"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-green-600 text-white hover:bg-green-700 transition">
                            Betaald
                        </button>
                    </form>
                @else
                    <span class="px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Betaald</span>
                @endhasanyrole
                @if ($order->payment_method)
                    <span class="text-[11px] text-gray-400">
                        {{ $order->payment_method === 'cash' ? 'Cash' : 'Overschrijving/Bancontact' }}
                    </span>
                @endif
            @else
                @hasanyrole('receptionist|admin|manager')
                    <form action="{{ route('orders.togglePaid', $order) }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method">
                        <button type="button"
                                @click="paymentForm = $el.closest('form'); paymentMode = 'pay'; paymentModalOpen = true"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-orange-500 text-white hover:bg-orange-600 transition">
                            Betalen
                        </button>
                    </form>
                @else
                    <span class="px-2.5 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700">Betalen</span>
                @endhasanyrole
            @endif
        </div>
    </td>

    <td class="px-6 py-4 text-sm text-center">
        <a href="{{ route('orders.show', $order) }}"
           class="px-2.5 py-1 rounded text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
            Details
        </a>
    </td>

    <td class="px-6 py-4 text-sm">
        <div class="flex flex-wrap items-center justify-end gap-2">
            {{-- Accept / refuse — receptionist/admin/manager only. Orderpicker
                 never sees these orders at all (see OrderController::index() filter).
                 Accepting syncs to Salesforce synchronously and can take a moment —
                 "syncing" is purely visual feedback so the click feels immediate
                 while that request is in flight. --}}
            @hasanyrole('receptionist|admin|manager')
                @if ($order->status === 'awaiting_review')
                    <form action="{{ route('orders.accept', $order) }}" method="POST"
                          x-data="{ syncing: false }" @submit="syncing = true">
                        @csrf
                        <button type="submit"
                                :disabled="syncing"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-green-600 text-white hover:bg-green-700 transition disabled:opacity-50">
                            <span x-show="!syncing">Accepteren</span>
                            <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                        </button>
                    </form>
                    <form action="{{ route('orders.refuse', $order) }}" method="POST"
                          x-data="{ syncing: false }" @submit="syncing = true">
                        @csrf
                        <button type="submit"
                                :disabled="syncing"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-red-700 text-white hover:bg-red-800 transition disabled:opacity-50">
                            <span x-show="!syncing">Weigeren</span>
                            <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                        </button>
                    </form>
                @endif
            @endhasanyrole

            {{-- Everything below is a receptionist/admin/manager job — orderpicker only views + picks (on the detail page) --}}
            @hasanyrole('receptionist|admin|manager')
                {{-- Cancel — only shown before the order has been synced --}}
                @if (in_array($order->status, ['awaiting_review', 'pending'], true))
                    <form action="{{ route('orders.cancel', $order) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-red-600 text-white hover:bg-red-700 transition">
                            Annuleren
                        </button>
                    </form>
                @endif

                {{-- Retry — for orders that failed to sync, or that got stuck on
                     'pending' (e.g. from before this app started processing orders
                     synchronously on accept) --}}
                @if (in_array($order->status, ['failed', 'pending'], true))
                    <form action="{{ route('orders.retry', $order) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-orange-600 text-white hover:bg-orange-700 transition">
                            Opnieuw proberen
                        </button>
                    </form>
                @endif

                {{-- Confirm the customer received the order — the final step --}}
                @if ($order->status === 'ready_for_pickup')
                    <form action="{{ route('orders.received', $order) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            Bevestig ontvangst
                        </button>
                    </form>
                @endif

            @endhasanyrole
        </div>
    </td>

    {{-- Print — its own column, separate from Actie --}}
    <td class="px-6 py-4 text-sm text-right">
        @hasanyrole('receptionist|admin|manager')
            <a href="{{ route('orders.print', $order) }}" target="_blank"
               class="inline-flex items-center gap-1 whitespace-nowrap px-2.5 py-1 rounded text-xs font-medium bg-gray-700 text-white hover:bg-gray-800 transition">
                <span>🖨</span><span>Print</span>
            </a>
        @else
            <span class="text-gray-300 text-xs">—</span>
        @endhasanyrole
    </td>
</tr>
