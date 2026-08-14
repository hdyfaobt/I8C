<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Klant bewerken — {{ $customer->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @csrf
                    @method('PATCH') {{-- Laravel uses PATCH for updates --}}

                    <x-form-field label="Naam" name="name" :value="old('name', $customer->name)" required />

                    <x-form-field label="E-mailadres" name="email" type="email" :value="old('email', $customer->email)" required />

                    <x-form-field label="Telefoonnummer" name="phone" :value="old('phone', $customer->phone)" />

                    <x-form-field label="Bedrijf" name="company" :value="old('company', $customer->company)" />

                    <x-form-field label="Adres" name="address" type="textarea" :value="old('address', $customer->address)" last />

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700">
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
