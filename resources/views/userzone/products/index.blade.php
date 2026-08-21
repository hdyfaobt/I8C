<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Producten
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8" x-data="{ deleteModalOpen: false, deleteForm: null, deleting: false, clientSearch: '' }">

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header row — only admin/manager can manage the catalog --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Productcatalogus</h3>
                @hasanyrole('admin|manager')
                    <a href="{{ route('products.create') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        + Product toevoegen
                    </a>
                @endhasanyrole
            </div>

            {{-- Search — artikelnummer, naam of prijs --}}
            <div class="mb-4">
                <input type="text"
                       x-model="clientSearch"
                       placeholder="Zoek op artikelnummer, naam of prijs..."
                       class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            {{-- Products table --}}
            <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="article_number" label="Artikelnummer" />
                            <x-sortable-header column="name" label="Naam" />
                            <x-sortable-header column="price" label="Prijs" />
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($products as $product)
                            @php
                                $productSearch = \Illuminate\Support\Str::lower(implode(' ', [
                                    $product->article_number,
                                    $product->name,
                                    number_format($product->price, 2, '.', ''),
                                    number_format($product->price, 2, ',', ''),
                                ]));
                            @endphp
                            <tr x-show="clientSearch === '' || {{ \Illuminate\Support\Js::from($productSearch) }}.includes(clientSearch.toLowerCase())">
                                <td class="px-4 py-4 text-sm font-mono text-gray-500">{{ $product->article_number }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    € {{ number_format($product->price, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-right text-sm whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        {{-- Order history — who ordered this product, how often, and when.
                                             Open to anyone who can see the catalog (receptionist/admin/manager). --}}
                                        <a href="{{ route('products.history', $product) }}" title="Geschiedenis"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                            📜
                                        </a>

                                        {{-- Manage the catalog — admin/manager only --}}
                                        @hasanyrole('admin|manager')
                                            <a href="{{ route('products.edit', $product) }}" title="Bewerken"
                                               class="inline-flex items-center justify-center w-8 h-8 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                                ✏️
                                            </a>

                                            {{-- Verwijderen — deliberately set apart (left border + extra
                                                 gap) and muted grey by default, so it can't be hit by
                                                 accident right after Bewerken. Confirms through the shared
                                                 modal (2 clicks), not a 1-click native confirm(). --}}
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline pl-2 border-l border-gray-200">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        title="Verwijderen"
                                                        @click="deleteForm = $el.closest('form'); deleteModalOpen = true"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 text-gray-400 hover:bg-red-100 hover:text-red-600 transition">
                                                    🗑️
                                                </button>
                                            </form>
                                        @endhasanyrole
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                    Geen producten gevonden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            <x-confirm-delete-modal message="Product verwijderen? Dit kan niet ongedaan gemaakt worden." />

        </div>
    </div>
</x-app-layout>
