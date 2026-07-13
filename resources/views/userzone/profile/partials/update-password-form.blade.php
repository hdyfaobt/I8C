<div>
    <h3 class="text-lg font-medium text-gray-900">Wachtwoord wijzigen</h3>
    <p class="text-sm text-gray-500 mt-1">Gebruik een lang, willekeurig wachtwoord om je account veilig te houden.</p>
</div>

<form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
    @csrf
    @method('put')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Huidig wachtwoord</label>
        <input type="password" name="current_password"
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               autocomplete="current-password">
        @error('current_password', 'updatePassword')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nieuw wachtwoord</label>
        <input type="password" name="password"
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               autocomplete="new-password">
        @error('password', 'updatePassword')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bevestig nieuw wachtwoord</label>
        <input type="password" name="password_confirmation"
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               autocomplete="new-password">
        @error('password_confirmation', 'updatePassword')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
            Opslaan
        </button>
        @if (session('status') === 'password-updated')
            <p x-data="{ show: true }" x-show="show" x-transition
               x-init="setTimeout(() => show = false, 2000)"
               class="text-sm text-green-600">Opgeslagen.</p>
        @endif
    </div>
</form>
