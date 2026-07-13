<div>
    <h3 class="text-lg font-medium text-gray-900">Profielgegevens</h3>
    <p class="text-sm text-gray-500 mt-1">Werk je naam en e-mailadres bij.</p>
</div>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
    @csrf
    @method('patch')

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}"
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               required autofocus autocomplete="name">
        @error('name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}"
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
               required autocomplete="username">
        @error('email')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-sm text-gray-600">
                    Je e-mailadres is niet geverifieerd.
                    <button form="send-verification" class="underline text-indigo-600 hover:text-indigo-900">
                        Verificatiemail opnieuw versturen.
                    </button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 text-sm font-medium text-green-600">
                        Een nieuwe verificatielink is verstuurd naar je e-mailadres.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
            Opslaan
        </button>
        @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition
               x-init="setTimeout(() => show = false, 2000)"
               class="text-sm text-green-600">Opgeslagen.</p>
        @endif
    </div>
</form>
