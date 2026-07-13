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
                             @js(route('customers.search')),
                             @js(route('customers.orders', ['customer' => '__ID__']))
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
                                   class="w-full rounded border-2 border-gray-300 px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">

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

                        {{-- Previous orders for the selected customer — this is where
                             reordering lives now instead of a "Herbestellen" button on
                             the whole orders list: pick a customer here and immediately
                             see (and reuse) what they ordered before. --}}
                        <div x-show="selectedId" x-cloak style="display: none;" class="mt-3">
                            <p class="text-xs font-medium text-gray-500 mb-1">Vorige bestellingen van deze klant</p>

                            <p x-show="loadingOrders" class="text-xs text-gray-400">Laden...</p>

                            <p x-show="!loadingOrders && pastOrders.length === 0" class="text-xs text-gray-400">
                                Nog geen bestellingen voor deze klant.
                            </p>

                            <ul x-show="!loadingOrders && pastOrders.length > 0" class="space-y-1">
                                <template x-for="pastOrder in pastOrders" :key="pastOrder.id">
                                    <li class="flex items-center justify-between gap-2 text-xs bg-gray-50 rounded px-3 py-2">
                                        <span class="truncate">
                                            <span x-text="pastOrder.created_at"></span> —
                                            <span x-text="pastOrder.items_summary"></span> —
                                            € <span x-text="pastOrder.total"></span>
                                        </span>
                                        <form :action="'{{ route('orders.repeat', ['order' => '__ID__']) }}'.replace('__ID__', pastOrder.id)"
                                              method="POST" class="shrink-0">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                Herbestel deze
                                            </button>
                                        </form>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Product lines — one order can have several products.               --}}
                    {{-- Alpine keeps the rows client-side; each row is submitted as         --}}
                    {{-- items[<index>][product|quantity|unit_price] so OrderController::store() --}}
                    {{-- receives a plain array it can loop over. Each row also has its own   --}}
                    {{-- search state (query/results/open/loading) so typing "salami" in one   --}}
                    {{-- row doesn't affect the others.                                        --}}
                    <div class="mb-4"
                         x-data="{
                             searchUrl: @js(route('products.search')),
                             // Rebuild each row with its search state (query/results/open) —
                             // old('items') after a validation error only has product_id/quantity/
                             // unit_price (that's all the form actually submits), so the search
                             // box's text ('query') is seeded from the product name to avoid
                             // showing an empty box even though a product was already selected.
                             items: (@js(old('items', [['product_id' => '', 'product' => '', 'quantity' => 1, 'unit_price' => '']]))).map(function (i) {
                                 return {
                                     product_id: i.product_id ?? '',
                                     product: i.product ?? '',
                                     quantity: i.quantity ?? 1,
                                     unit_price: i.unit_price ?? '',
                                     query: i.product ?? '',
                                     results: [],
                                     open: false,
                                 };
                             }),
                             addItem() {
                                 this.items.push({ product_id: '', product: '', quantity: 1, unit_price: '', query: '', results: [], open: false });
                             },
                             removeItem(index) {
                                 if (this.items.length > 1) this.items.splice(index, 1);
                             },
                             async searchProduct(index) {
                                 // Typing invalidates whatever was selected before — only
                                 // clicking a suggestion below sets product_id/product again.
                                 // This is what enforces 'existing products only': nothing
                                 // gets submitted for this row until a real catalog product
                                 // is picked (see also OrderController::store(), which looks
                                 // the product up server-side by this id).
                                 this.items[index].product_id = '';
                                 this.items[index].product = '';

                                 const q = this.items[index].query;
                                 if (q.length === 0) { this.items[index].results = []; return; }
                                 const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q)}`);
                                 this.items[index].results = await response.json();
                                 this.items[index].open = true;
                             },
                             selectProduct(index, product) {
                                 this.items[index].product_id = product.id;
                                 this.items[index].product = product.name;
                                 this.items[index].unit_price = product.price;
                                 this.items[index].query = `${product.name} (${product.article_number})`;
                                 this.items[index].open = false;
                                 this.items[index].results = [];
                             }
                         }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Producten *</label>

                        {{-- Validation errors for any items.* field — shown together since    --}}
                        {{-- the rows are rendered client-side and can't easily be matched      --}}
                        {{-- back to a specific Blade @error() after a redisplay.               --}}
                        @if ($errors->has('items') || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'items.')))
                            <div class="mb-2 space-y-1">
                                @foreach ($errors->keys() as $key)
                                    @if ($key === 'items' || str_starts_with($key, 'items.'))
                                        <p class="text-red-500 text-xs">{{ $errors->first($key) }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-12 gap-2 mb-2 items-start">
                                {{-- Product search combobox — type to search the catalog        --}}
                                {{-- (e.g. "salami", "chèvre") and pick a result. Only an actual   --}}
                                {{-- catalog product can end up in this row: typing without        --}}
                                {{-- selecting a suggestion clears product_id/product again (see    --}}
                                {{-- searchProduct() above), so free text never gets submitted.     --}}
                                <div class="col-span-6 relative">
                                    <input type="text"
                                           x-model="item.query"
                                           @input.debounce.300ms="searchProduct(index)"
                                           @focus="item.open = true"
                                           @click.outside="item.open = false"
                                           autocomplete="off"
                                           placeholder="Zoek een product uit de catalogus (bv. salami, chèvre...)"
                                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">

                                    {{-- Not selected yet? Make that obvious instead of silently
                                         accepting whatever's typed. --}}
                                    <p x-show="item.query.length > 0 && !item.product_id" class="text-xs text-amber-600 mt-1">
                                        Kies een product uit de lijst.
                                    </p>

                                    {{-- The actual values submitted with the form —
                                         product_id is what OrderController::store() validates
                                         against; product is only kept for redisplay after an
                                         error (see the old('items') mapping above). --}}
                                    <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                                    <input type="hidden" :name="`items[${index}][product]`" x-model="item.product">

                                    <div x-show="item.open && item.results.length > 0"
                                         x-transition
                                         class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto"
                                         style="display: none;">
                                        <template x-for="product in item.results" :key="product.id">
                                            <button type="button"
                                                    @click="selectProduct(index, product)"
                                                    class="w-full text-left px-4 py-2 hover:bg-indigo-50 text-sm">
                                                <span x-text="product.name" class="font-medium text-gray-900"></span>
                                                <span x-text="' — ' + product.article_number + ' — € ' + product.price" class="text-gray-500"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity"
                                           min="1" placeholder="Aantal"
                                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" :name="`items[${index}][unit_price]`" x-model="item.unit_price"
                                           step="0.01" min="0" placeholder="Prijs (€)"
                                           class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div class="col-span-1 text-right">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                            class="text-red-500 hover:text-red-700 text-sm" title="Verwijderen">
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </template>

                        <button type="button" @click="addItem()"
                                class="mt-1 text-sm text-indigo-600 hover:text-indigo-900">
                            + Product toevoegen
                        </button>
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
