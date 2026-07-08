<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestelling plaatsen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('orders.store') }}">
                    @csrf

                    {{-- Customer search combobox — queries customers.search as you type       --}}
                    {{-- instead of loading every customer into a giant <select> (doesn't scale). --}}
                    <div class="mb-4"
                         x-data="customerSearch(
                             @js($selectedCustomer),
                             @js(route('customers.search'))
                         )">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Klant *</label>

                        <div class="relative">
                            <input type="text"
                                   x-model="query"
                                   @input.debounce.300ms="search()"
                                   @focus="onFocus()"
                                   @click.outside="open = false"
                                   autocomplete="off"
                                   placeholder="Zoek op naam, e-mail of bedrijf..."
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                            {{-- Actual value submitted with the form --}}
                            <input type="hidden" name="customer_id" x-model="selectedId">

                            {{-- Search results --}}
                            <div x-show="open && results.length > 0"
                                 x-transition
                                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto"
                                 style="display: none;">
                                <template x-for="customer in results" :key="customer.id">
                                    <button type="button"
                                            @click="select(customer)"
                                            class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-sm">
                                        <span x-text="customer.name" class="font-medium text-gray-900"></span>
                                        <span x-text="customer.company ? ' — ' + customer.company : ' — ' + customer.email" class="text-gray-500"></span>
                                    </button>
                                </template>
                            </div>

                            {{-- No results --}}
                            <div x-show="open && !loading && results.length === 0"
                                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg px-4 py-2 text-sm text-gray-400"
                                 style="display: none;">
                                Geen klanten gevonden.
                            </div>
                        </div>

                        @error('customer_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Product --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                        <input type="text" name="product" value="{{ old('product') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Productnaam">
                        @error('product')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Quantity + Price side by side --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Aantal *</label>
                            <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('quantity')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Eenheidsprijs (€) *</label>
                            <input type="number" name="unit_price" value="{{ old('unit_price') }}"
                                   step="0.01" min="0"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="0.00">
                            @error('unit_price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Opmerkingen</label>
                        <textarea name="notes" rows="3"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Extra info voor deze bestelling...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('orders.index') }}" class="text-gray-500 hover:text-gray-700">
                            Annuleren
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Bestelling plaatsen
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
