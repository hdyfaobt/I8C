<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Account toevoegen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <x-form-field label="Naam" name="name" :value="old('name')" placeholder="Jan Janssen" required />

                    <x-form-field label="E-mailadres" name="email" type="email" :value="old('email')" placeholder="jan@i8c.be" required />

                    <x-form-field label="Wachtwoord" name="password" type="password" placeholder="Minstens 8 tekens" required />

                    {{-- Role — defaults to orderpicker, still freely changeable --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                        <select name="role"
                                class="w-full rounded-md border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            @foreach ($roles as $role)
                                {{-- The submitted value stays lowercase — only the label is capitalized --}}
                                <option value="{{ $role }}" {{ old('role', $defaultRole) === $role ? 'selected' : '' }}>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700">
                            Annuleren
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Account opslaan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
