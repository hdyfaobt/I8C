<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gebruikersbeheer
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="{ deleteModalOpen: false, deleteForm: null }">

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error message (e.g. tried to delete own account) --}}
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header row: title + add button --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Overzicht accounts</h3>
                <a href="{{ route('admin.users.create') }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                    + Account toevoegen
                </a>
            </div>

            {{-- Users table --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-sortable-header column="name" label="Naam" />
                            <x-sortable-header column="email" label="E-mail" />
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @foreach ($user->roles as $role)
                                        <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs">
                                            {{-- Capitalized for display only — the stored role name stays lowercase --}}
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs">
                                            Geen rol
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="text-indigo-600 hover:text-indigo-900">Bewerken</a>

                                    {{-- Disabled for your own account server-side (see UserController::destroy()) --}}
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                @click="deleteForm = $el.closest('form'); deleteModalOpen = true"
                                                class="text-red-600 hover:text-red-900">
                                            Verwijderen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Geen accounts gevonden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-confirm-delete-modal message="Account verwijderen? Dit kan niet ongedaan gemaakt worden." />

        </div>
    </div>
</x-app-layout>
