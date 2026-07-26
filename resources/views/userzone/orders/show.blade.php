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
             x-data="{ paymentModalOpen: false, paymentForm: null, paymentMode: 'pay', paying: false, deleteModalOpen: false, deleteForm: null, deleting: false }">

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
                        <p class="text-gray-500">Klantnummer</p>
                        <p class="font-medium font-mono">{{ $order->customer->customerNumber() }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Bedrijf</p>
                        <p class="font-medium">{{ $order->customer->company ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Totaal</p>
                        <p class="font-medium text-indigo-600">€ {{ number_format($order->totalPrice(), 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Besteldatum</p>
                        <p class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if ($order->accepted_at)
                    <div>
                        <p class="text-gray-500">Geaccepteerd op</p>
                        <p class="font-medium">{{ $order->accepted_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if ($order->status === 'sent' && $order->sent_at)
                    <div>
                        <p class="text-gray-500">Verzonden op</p>
                        <p class="font-medium">{{ $order->sent_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @elseif ($order->status === 'failed' && $order->failed_at)
                    <div>
                        <p class="text-gray-500">Mislukt op</p>
                        <p class="font-medium">{{ $order->failed_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @elseif ($order->status === 'refused' && $order->refused_at)
                    <div>
                        <p class="text-gray-500">Geweigerd op</p>
                        <p class="font-medium">{{ $order->refused_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @elseif ($order->status === 'cancelled' && $order->cancelled_at)
                    <div>
                        <p class="text-gray-500">Geannuleerd op</p>
                        <p class="font-medium">{{ $order->cancelled_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @elseif ($order->status === 'ready_for_pickup' && $order->ready_at)
                    <div>
                        <p class="text-gray-500">Klaar op</p>
                        <p class="font-medium">{{ $order->ready_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @elseif ($order->status === 'received' && $order->received_at)
                    <div>
                        <p class="text-gray-500">Ontvangen op</p>
                        <p class="font-medium">{{ $order->received_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif

                    {{-- Who did what — the three key hand-offs in this order's
                         lifecycle. Each is only filled in once that step has
                         actually happened (see OrderController::store()/repeat(),
                         markReady(), markReceived()). --}}
                    <div>
                        <p class="text-gray-500">Besteld door</p>
                        <p class="font-medium">{{ $order->createdBy->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Voorbereid door</p>
                        <p class="font-medium">{{ $order->preparedBy->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Afgeleverd door</p>
                        <p class="font-medium">{{ $order->receivedBy->name ?? '—' }}</p>
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
                        <p class="mt-1 p-2 bg-amber-50 border border-amber-200 rounded text-sm font-medium text-gray-800">{{ $order->notes }}</p>
                    </div>
                    @endif
                </div>

                <hr>

                {{-- Product lines --}}
                <div>
                    <p class="text-gray-500 text-sm mb-2">Producten</p>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase">
                                <th class="pb-1">Product</th>
                                <th class="pb-1">Aantal</th>
                                <th class="pb-1">Prijs</th>
                                <th class="pb-1">Subtotaal</th>
                                @hasanyrole('orderpicker|admin|manager')
                                    <th class="pb-1 text-right">Status</th>
                                @endhasanyrole
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                {{-- Out of stock — struck through, it's not actually being delivered --}}
                                <tr class="{{ $item->isOutOfStock() ? 'line-through text-gray-400' : '' }}">
                                    <td class="py-2">{{ $item->product }}</td>
                                    <td class="py-2">{{ $item->quantity }}</td>
                                    <td class="py-2">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                    <td class="py-2">€ {{ number_format($item->lineTotal(), 2, ',', '.') }}</td>
                                    @hasanyrole('orderpicker|admin|manager')
                                        <td class="py-2 text-right">
                                            @if (($order->status === 'sent' || $order->status === 'ready_for_pickup') && $canWorkOnPicking)
                                                <div class="flex items-center justify-end gap-1">
                                                    <form action="{{ route('orders.items.pick', [$order, $item]) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-2 py-1 rounded text-xs whitespace-nowrap {{ $item->isPicked() ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-green-100/70 text-green-700 hover:bg-green-100' }}">
                                                            Opgehaald
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('orders.items.outOfStock', [$order, $item]) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-2 py-1 rounded text-xs whitespace-nowrap {{ $item->isOutOfStock() ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-red-100/70 text-red-700 hover:bg-red-100' }}">
                                                            Niet beschikbaar
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif ($item->isPicked())
                                                <span class="text-xs text-green-600">Opgehaald</span>
                                            @elseif ($item->isOutOfStock())
                                                <span class="text-xs text-red-600">Niet beschikbaar</span>
                                            @else
                                                <span class="text-gray-300 text-xs">—</span>
                                            @endif
                                        </td>
                                    @endhasanyrole
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Picking comment — left by the orderpicker while preparing the order --}}
                @if ($order->picking_comment || $order->status === 'sent')
                <hr>
                <div>
                    <p class="text-gray-500 text-sm mb-2">Opmerking van de orderpicker</p>
                    @hasanyrole('orderpicker|admin|manager')
                        @if (($order->status === 'sent' || $order->status === 'ready_for_pickup') && $canWorkOnPicking)
                            <form action="{{ route('orders.pickingComment', $order) }}" method="POST" class="space-y-2">
                                @csrf
                                <textarea name="picking_comment" rows="2"
                                          class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Bv. product X niet op voorraad, vervangen door Y...">{{ old('picking_comment', $order->picking_comment) }}</textarea>
                                <button type="submit"
                                        class="px-3 py-1.5 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xs">
                                    Opmerking opslaan
                                </button>
                            </form>
                        @elseif ($order->picking_comment)
                            <p class="p-2 bg-amber-50 border border-amber-200 rounded text-sm font-medium text-gray-800">{{ $order->picking_comment }}</p>
                        @endif
                    @else
                        @if ($order->picking_comment)
                            <p class="p-2 bg-amber-50 border border-amber-200 rounded text-sm font-medium text-gray-800">{{ $order->picking_comment }}</p>
                        @else
                            <p class="text-sm text-gray-400">—</p>
                        @endif
                    @endhasanyrole
                </div>
                @endif

                <hr>

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Accept / refuse — while awaiting review. Receptionist/admin/manager
                         only: orderpicker's job starts once the order has already been
                         accepted and synced to Salesforce (status 'sent'). Accepting syncs
                         to Salesforce synchronously and can take a moment — "syncing" is
                         purely visual feedback so the click feels immediate while that
                         request is in flight. --}}
                    @hasanyrole('receptionist|admin|manager')
                        @if ($order->status === 'awaiting_review')
                            <form action="{{ route('orders.accept', $order) }}" method="POST"
                                  x-data="{ syncing: false }" @submit="syncing = true">
                                @csrf
                                <button type="submit"
                                        :disabled="syncing"
                                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm disabled:opacity-50">
                                    <span x-show="!syncing">Accepteren</span>
                                    <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                                </button>
                            </form>
                            <form action="{{ route('orders.refuse', $order) }}" method="POST"
                                  x-data="{ syncing: false }" @submit="syncing = true">
                                @csrf
                                <button type="submit"
                                        :disabled="syncing"
                                        class="px-4 py-2 bg-red-700 text-white rounded hover:bg-red-800 transition text-sm disabled:opacity-50">
                                    <span x-show="!syncing">Weigeren</span>
                                    <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                                </button>
                            </form>
                        @endif
                    @endhasanyrole

                    {{-- Orderpicker: confirm the order is fully picked and ready for pickup --}}
                    @hasanyrole('orderpicker|admin|manager')
                        @if ($order->status === 'sent' && $canWorkOnPicking)
                            <form action="{{ route('orders.ready', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        @unless ($order->allItemsResolved()) disabled @endunless
                                        class="px-4 py-2 rounded transition text-sm {{ $order->allItemsResolved() ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                                    Klaar om op te halen
                                </button>
                            </form>
                            @unless ($order->allItemsResolved())
                                <span class="text-xs text-gray-400">Markeer eerst alle producten als opgehaald of niet op voorraad.</span>
                            @endunless
                        @elseif ($order->status === 'sent' && ! $canWorkOnPicking)
                            <span class="text-xs text-gray-400">Neem deze bestelling over om te beginnen.</span>
                        @endif
                    @endhasanyrole

                    @hasanyrole('receptionist|admin|manager')
                        @if (in_array($order->status, ['awaiting_review', 'pending'], true))
                            <form action="{{ route('orders.cancel', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm">
                                    Annuleren
                                </button>
                            </form>
                        @endif

                        @if (in_array($order->status, ['failed', 'pending'], true))
                            <form action="{{ route('orders.retry', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition text-sm">
                                    Opnieuw proberen
                                </button>
                            </form>
                        @endif

                        @if ($order->status === 'ready_for_pickup')
                            <form action="{{ route('orders.received', $order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition text-sm">
                                    Bevestig ontvangst door klant
                                </button>
                            </form>
                        @endif

                        {{-- Toggle "paid" — independent from the status lifecycle, can be
                             clicked at any point regardless of where the order is above.
                             Reads "Betalen" before payment (opens the site's own confirmation
                             alert asking for a payment method), turns into "Betaald" once
                             clicked. Undoing a payment (once already paid) is admin/manager
                             only — a receptionist sees a read-only badge instead, see
                             OrderController::togglePaid() for the same rule server-side.
                             A cancelled order can never be paid — nothing to collect
                             payment for, see the same guard in togglePaid(). --}}
                        @if ($order->status === 'cancelled')
                            <span class="px-4 py-2 rounded text-sm bg-gray-100 text-gray-400">Niet van toepassing</span>
                        @elseif ($order->paid)
                            @hasanyrole('admin|manager')
                                <form action="{{ route('orders.togglePaid', $order) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="payment_method">
                                    <button type="button"
                                            @click="paymentForm = $el.closest('form'); paymentMode = 'revert'; paymentModalOpen = true"
                                            class="px-4 py-2 rounded transition text-sm bg-emerald-600 text-white hover:bg-emerald-700">
                                        Betaald ✓
                                    </button>
                                </form>
                            @else
                                <span class="px-4 py-2 rounded text-sm bg-emerald-100 text-emerald-700">Betaald ✓</span>
                            @endhasanyrole
                        @else
                            <form action="{{ route('orders.togglePaid', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" name="payment_method">
                                <button type="button"
                                        @click="paymentForm = $el.closest('form'); paymentMode = 'pay'; paymentModalOpen = true"
                                        class="px-4 py-2 rounded transition text-sm bg-indigo-600 text-white hover:bg-indigo-700">
                                    Betalen
                                </button>
                            </form>
                        @endif

                        {{-- Print a printable invoice sheet in a new tab --}}
                        <a href="{{ route('orders.print', $order) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 whitespace-nowrap px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800 transition text-sm">
                            <span>🖨</span><span>Print factuur</span>
                        </a>
                    @endhasanyrole

                    {{-- Permanent delete — admin only, for broken/empty orders --}}
                    @role('admin')
                        <form action="{{ route('orders.destroy', $order) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                    @click="deleteForm = $el.closest('form'); deleteModalOpen = true"
                                    class="px-4 py-2 bg-gray-200 text-red-700 rounded hover:bg-gray-300 transition text-sm">
                                Verwijderen
                            </button>
                        </form>
                    @endrole
                </div>

                <hr>

                <a href="{{ route('orders.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                    ← Terug naar bestellingen
                </a>
            </div>

            {{-- Custom payment confirmation alert — same component as orders/index.blade.php.
                 Green while confirming a payment (asks for the payment method too), orange
                 while undoing one. Replaces the native browser confirm() popup. --}}
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
                                        :disabled="paying"
                                        @click="paying = true; paymentForm.querySelector('[name=payment_method]').value = 'bank_transfer'; paymentForm.requestSubmit()"
                                        class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition disabled:opacity-50">
                                    <span x-show="!paying">Overschrijving / Bancontact</span>
                                    <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                                </button>
                                <button type="button"
                                        :disabled="paying"
                                        @click="paying = true; paymentForm.querySelector('[name=payment_method]').value = 'cash'; paymentForm.requestSubmit()"
                                        class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition disabled:opacity-50">
                                    <span x-show="!paying">Cash</span>
                                    <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                                </button>
                            </div>
                            <div class="mt-3 flex justify-end" x-show="!paying">
                                <button type="button"
                                        @click="paymentModalOpen = false"
                                        class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                                    Annuleren
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Undoing a payment — admin/manager only, plain confirm --}}
                    <template x-if="paymentMode === 'revert'">
                        <div>
                            <p class="text-sm font-medium text-orange-800">
                                Bevestig: deze bestelling markeren als NIET betaald?
                            </p>
                            <div class="mt-4 flex justify-end gap-2">
                                <button type="button"
                                        x-show="!paying"
                                        @click="paymentModalOpen = false"
                                        class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                                    Annuleren
                                </button>
                                <button type="button"
                                        :disabled="paying"
                                        @click="paying = true; paymentForm.requestSubmit()"
                                        class="px-3 py-1.5 rounded text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50">
                                    <span x-show="!paying">Bevestigen</span>
                                    <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <x-confirm-delete-modal message="Bestelling #{{ $order->id }} definitief verwijderen? Dit kan niet ongedaan gemaakt worden." />
        </div>
    </div>
</x-app-layout>
