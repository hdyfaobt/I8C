<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gebruikersbeheer
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

            {{-- Error message (e.g. tried to delete own account) --}}
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header row: title + add button — admin and manager --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Overzicht accounts</h3>
                @hasanyrole('admin|manager')
                    <a href="{{ route('admin.users.create') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        + Account toevoegen
                    </a>
                @endhasanyrole
            </div>

            {{-- Search — naam, e-mail of rol --}}
            <div class="mb-4">
                <input type="text"
                       x-model="clientSearch"
                       placeholder="Zoek op naam, e-mail of rol..."
                       class="w-full max-w-sm rounded border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            {{-- Users table --}}
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
                            @php
                                $userSearch = \Illuminate\Support\Str::lower(implode(' ', [
                                    $user->name,
                                    $user->email,
                                    $user->roles->pluck('name')->implode(' '),
                                ]));
                            @endphp
                            <tr x-show="clientSearch === '' || {{ \Illuminate\Support\Js::from($userSearch) }}.includes(clientSearch.toLowerCase())">
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
                                    {{-- A manager can't touch an admin's or another manager's account --}}
                                    @if (auth()->user()->hasRole('admin') || ! $user->hasAnyRole(['admin', 'manager']))
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="text-indigo-600 hover:text-indigo-900">Bewerken</a>
                                    @else
                                        <span class="text-gray-300">Bewerken</span>
                                    @endif

                                    {{-- Deleting stays admin-only, disabled for your own account server-side too (see UserController::destroy()) --}}
                                    @role('admin')
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    @click="deleteForm = $el.closest('form'); deleteModalOpen = true"
                                                    class="text-red-600 hover:text-red-900">
                                                Verwijderen
                                            </button>
                                        </form>
                                    @endrole
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

            <x-confirm-delete-modal message="Account verwijderen? Dit kan niet ongedaan gemaakt worden." />

        </div>
    </div>
</x-app-layout>
