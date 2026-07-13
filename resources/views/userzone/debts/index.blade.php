<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Openstaande schulden
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Summary — total still owed to us, across every customer --}}
            <div class="mb-6 p-4 rounded-lg border {{ $debts->isEmpty() ? 'bg-gray-50 border-gray-200' : 'bg-red-50 border-red-200' }}">
                <p class="text-sm text-gray-500">Totaal nog te ontvangen van klanten</p>
                <p class="text-2xl font-semibold {{ $debts->isEmpty() ? 'text-gray-400' : 'text-red-700' }}">
                    € {{ number_format($grandTotal, 2, ',', '.') }}
                </p>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-medium text-gray-900">Per klant</h3>
                    <p class="text-sm text-gray-400">
                        Onbetaalde bestellingen — producten die niet meer op voorraad waren, tellen hier niet mee (zie Terugbetalingen).
                    </p>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="customer" label="Klant" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Openstaande bestellingen</th>
                            <x-sortable-header column="total" label="Totaal verschuldigd" align="right" />
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($debts as $row)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 align-top">
                                    {{ $row['customer']->name }}
                                    <br><span class="text-gray-400 text-xs font-mono">{{ $row['customer']->customerNumber() }}</span>
                                    @if ($row['customer']->company)
                                        <br><span class="text-gray-400 text-xs">{{ $row['customer']->company }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($row['orders'] as $order)
                                            <a href="{{ route('orders.show', $order) }}"
                                               class="px-2 py-1 rounded text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition whitespace-nowrap">
                                                #{{ $order->id }} — € {{ number_format($order->outstandingBalance(), 2, ',', '.') }}
                                            </a>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-red-700 align-top whitespace-nowrap">
                                    € {{ number_format($row['total'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                                    Geen enkele klant heeft nog een openstaand bedrag.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
