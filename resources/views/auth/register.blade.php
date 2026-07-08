<x-guest-layout>

    {{-- Section title --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold" style="color: #1e2235;">Account aanmaken</h2>
        <p class="text-sm text-gray-500 mt-1">Vul uw gegevens in om aan de slag te gaan</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Naam -->
        <div>
            <x-breeze.input-label for="name" value="Naam" />
            <x-breeze.text-input
                id="name"
                class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
            />
            <x-breeze.input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- E-mailadres -->
        <div class="mt-4">
            <x-breeze.input-label for="email" value="E-mailadres" />
            <x-breeze.text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autocomplete="username"
            />
            <x-breeze.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Wachtwoord -->
        <div class="mt-4">
            <x-breeze.input-label for="password" value="Wachtwoord" />
            <x-breeze.text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password"
            />
            <x-breeze.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Wachtwoord bevestigen -->
        <div class="mt-4">
            <x-breeze.input-label for="password_confirmation" value="Wachtwoord bevestigen" />
            <x-breeze.text-input
                id="password_confirmation"
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-breeze.input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Actions -->
        <div class="mt-6">
            {{-- Main register button --}}
            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: #da532c;">
                Account aanmaken
            </button>
        </div>

        <!-- Terug naar inloggen -->
        <div class="mt-4 text-center">
            <a href="{{ route('login') }}"
               class="text-sm text-gray-500 hover:underline">
                Al een account? Aanmelden
            </a>
        </div>
    </form>

</x-guest-layout>
