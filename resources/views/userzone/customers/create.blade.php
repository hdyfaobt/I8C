<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Klant toevoegen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf

                    {{-- Name --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Naam *</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Jan Janssen">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres *</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="jan@bedrijf.be">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefoonnummer</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="+32 470 00 00 00">
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Company --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bedrijf</label>
                        <input type="text" name="company" value="{{ old('company') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Bedrijf NV">
                        @error('company')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                        <textarea name="address" rows="3"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Straat 1, 1000 Brussel">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700">
                            Annuleren
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Klant opslaan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
