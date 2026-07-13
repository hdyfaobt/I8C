<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Account bewerken — {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">

                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- Name --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Naam *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password — optional, only changed when filled in --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nieuw wachtwoord</label>
                        <input type="password" name="password"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Laat leeg om ongewijzigd te laten">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                        <select name="role"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach ($roles as $role)
                                {{-- The submitted value stays lowercase — only the label is capitalized --}}
                                <option value="{{ $role }}" {{ old('role', $user->roles->first()?->name) === $role ? 'selected' : '' }}>
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
                            Wijzigingen opslaan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
