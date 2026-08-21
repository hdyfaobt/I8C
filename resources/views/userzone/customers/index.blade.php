<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Klanten
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8" x-data="{ clientSearch: '' }">

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error message — e.g. a failed Salesforce sync attempt --}}
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header row: title + add button --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Overzicht klanten</h3>
                <a href="{{ route('customers.create') }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                    + Klant toevoegen
                </a>
            </div>

            {{-- Client-side search — filters by naam, bedrijf and e-mail,
                 no page reload (same pattern as the orders overview). --}}
            <div class="mb-4">
                <input type="text"
                       x-model="clientSearch"
                       placeholder="Zoek op klant, bedrijf of e-mail..."
                       class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            {{-- Customers table — wrapped in overflow-x-auto so an overflow
                 scrolls just the table, never the whole page. Naam links
                 straight to the detail page, so there's no separate "Details"
                 column taking up width. --}}
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="id" label="Klantnummer" />
                            <x-sortable-header column="name" label="Naam" />
                            <x-sortable-header column="company" label="Bedrijf" />
                            <x-sortable-header column="email" label="E-mail" />
                            <x-sortable-header column="phone" label="Telefoon" />
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salesforce</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($customers as $customer)
                            <tr x-show="clientSearch === '' || {{ \Illuminate\Support\Js::from(\Illuminate\Support\Str::lower($customer->name.' '.($customer->company ?? '').' '.$customer->email)) }}.includes(clientSearch.toLowerCase())">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                    {{ $customer->customerNumber() }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('customers.show', $customer) }}" class="hover:text-indigo-600 hover:underline">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                    {{ $customer->company ?? '—' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $customer->email }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $customer->phone ?? '—' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    @if ($customer->salesforce_id)
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                            Gesynchroniseerd
                                        </span>
                                    @else
                                        {{-- Plain POST + server redirect — the page reloads on its own
                                             once Salesforce responds. The "syncing" flag here is purely
                                             visual, so the click feels immediate while that request is
                                             in flight (it can take a second or two). --}}
                                        <form action="{{ route('customers.syncSalesforce', $customer) }}" method="POST"
                                              class="inline" x-data="{ syncing: false }" @submit="syncing = true">
                                            @csrf
                                            <button type="submit"
                                                    :disabled="syncing"
                                                    class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs hover:bg-yellow-200 transition disabled:opacity-50">
                                                <span x-show="!syncing">Klant bevestigen</span>
                                                <span x-show="syncing" x-cloak style="display: none;">Bezig...</span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('customers.edit', $customer) }}" title="Bewerken"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                        ✏️
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                    Geen klanten gevonden. Voeg je eerste klant toe.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
