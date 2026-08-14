<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Product bewerken — {{ $product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('products.update', $product) }}">
                    @csrf
                    @method('PUT')

                    <x-form-field label="Artikelnummer" name="article_number" :value="old('article_number', $product->article_number)" required />

                    <x-form-field label="Naam" name="name" :value="old('name', $product->name)" required />

                    <x-form-field label="Prijs (€)" name="price" type="number" :value="old('price', $product->price)" :step="0.01" :min="0" required last />

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700">
                            Annuleren
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Wijzigingen opslaan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
