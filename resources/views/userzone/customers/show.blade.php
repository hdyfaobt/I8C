<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $customer->name }}
            <span class="text-sm font-normal text-gray-400">— {{ $customer->customerNumber() }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ deleteModalOpen: false, deleteForm: null }">

            {{-- Success message --}}
            @if (session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error message — e.g. a failed Salesforce sync attempt --}}
            @if (session('error'))
                <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Customer details card --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 space-y-4">

                {{-- Salesforce sync status --}}
                <div class="flex items-center gap-3">
                    @if ($customer->salesforce_id)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">✓ Gesynchroniseerd met Salesforce</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">⏳ In afwachting van synchronisatie</span>
                        {{-- Plain POST + server redirect — the page reloads on its own once
                             Salesforce responds. "syncing" is purely visual feedback while
                             that request is in flight. --}}
                        <form action="{{ route('customers.syncSalesforce', $customer) }}" method="POST"
                              x-data="{ syncing: false }" @submit="syncing = true">
                            @csrf
                            <button type="submit"
                                    :disabled="syncing"
                                    class="px-3 py-1 bg-indigo-600 text-white rounded-full text-sm hover:bg-indigo-700 transition disabled:opacity-50">
                                <span x-show="!syncing">Klant bevestigen</span>
                                <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                            </button>
                        </form>
                    @endif
                </div>

                <hr>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Klantnummer</p>
                        <p class="font-medium font-mono">{{ $customer->customerNumber() }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Naam</p>
                        <p class="font-medium">{{ $customer->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">E-mail</p>
                        <p class="font-medium">{{ $customer->email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Telefoon</p>
                        <p class="font-medium">{{ $customer->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Bedrijf</p>
                        <p class="font-medium">{{ $customer->company ?? '—' }}</p>
                    </div>
                    @if ($customer->address)
                    <div class="col-span-2">
                        <p class="text-gray-500">Adres</p>
                        <p class="font-medium">{{ $customer->address }}</p>
                    </div>
                    @endif
                    @if ($customer->salesforce_id)
                    <div class="col-span-2">
                        <p class="text-gray-500">Salesforce ID</p>
                        <p class="font-mono text-xs">{{ $customer->salesforce_id }}</p>
                    </div>
                    @endif
                </div>

                <hr>

                <div class="flex items-center justify-between">
                    <a href="{{ route('customers.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                        ← Terug naar klanten
                    </a>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('customers.edit', $customer) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                            Bewerken
                        </a>
                        @role('admin')
                            @if ($customer->orders->isEmpty())
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            @click="deleteForm = $el.closest('form'); deleteModalOpen = true"
                                            class="text-red-600 hover:text-red-900 text-sm">
                                        Verwijderen
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-300 text-sm" title="Klant heeft bestellingen">Verwijderen</span>
                            @endif
                        @endrole
                    </div>
                </div>
            </div>

            <x-confirm-delete-modal message="Klant verwijderen? Dit kan niet ongedaan gemaakt worden." />

            {{-- Order history card --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Bestelgeschiedenis</h3>
                    @hasanyrole('receptionist|admin|manager')
                        <a href="{{ route('orders.create') }}"
                           class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-sm">
                            + Bestelling plaatsen
                        </a>
                    @endhasanyrole
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aantal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($customer->orders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $order->items->pluck('product')->join(', ') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->items->sum('quantity') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    € {{ number_format($order->totalPrice(), 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($order->status === 'sent')
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
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Ontvangen</span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">In afwachting</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="text-indigo-600 hover:text-indigo-900">Details</a>

                                    {{-- Repeat this order — same product lines, new order ID —
                                         receptionist/admin/manager only, same as the route's role gate --}}
                                    @hasanyrole('receptionist|admin|manager')
                                        <form action="{{ route('orders.repeat', $order) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Deze bestelling opnieuw plaatsen?')">
                                            @csrf
                                            <button type="submit" class="text-gray-500 hover:text-gray-900">
                                                Opnieuw bestellen
                                            </button>
                                        </form>
                                    @endhasanyrole
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                    Nog geen bestellingen voor deze klant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
