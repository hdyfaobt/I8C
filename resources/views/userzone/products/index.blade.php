<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Producten
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8">

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

            {{-- Products table --}}
            <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="article_number" label="Artikelnummer" />
                            <x-sortable-header column="name" label="Naam" />
                            <x-sortable-header column="price" label="Prijs" />
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-6 py-4 text-sm font-mono text-gray-500">{{ $product->article_number }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    € {{ number_format($product->price, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                    {{-- Order history — who ordered this product, how often, and when.
                                         Open to anyone who can see the catalog (receptionist/admin/manager). --}}
                                    <a href="{{ route('products.history', $product) }}"
                                       class="text-gray-600 hover:text-gray-900">Geschiedenis</a>

                                    {{-- Manage the catalog — admin/manager only --}}
                                    @hasanyrole('admin|manager')
                                        <a href="{{ route('products.edit', $product) }}"
                                           class="text-indigo-600 hover:text-indigo-900">Bewerken</a>

                                        <form action="{{ route('products.destroy', $product) }}"
                                              method="POST" class="inline"
                                              onsubmit="return confirm('Product \'{{ $product->name }}\' verwijderen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Verwijderen
                                            </button>
                                        </form>
                                    @endhasanyrole
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Geen producten gevonden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

        </div>
    </div>
</x-app-layout>
