<x-guest-layout>

    {{-- Section title --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold" style="color: #1e2235;">Aanmelden</h2>
        <p class="text-sm text-gray-500 mt-1">Voer uw gegevens in om toegang te krijgen</p>
    </div>

    <!-- Session status message -->
    <x-breeze.auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- E-mailadres -->
        <div>
            <x-breeze.input-label for="email" value="E-mailadres" />
            <x-breeze.text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
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
                autocomplete="current-password"
            />
            <x-breeze.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Onthoud mij -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 shadow-sm focus:ring-2"
                    style="color: #da532c; accent-color: #da532c;"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">Onthoud mij</span>
            </label>
        </div>

        <!-- Actions -->
        <div class="mt-6">
            {{-- Main login button --}}
            <button type="submit"
                    class="w-full py-2.5 px-4 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: #da532c; focus-ring-color: #da532c;">
                Aanmelden
            </button>
        </div>

        <div class="mt-6">

            <button type="register"
            class="w-full py-2.5 px-4 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: #1e2235; focus-ring-color: #1e2235;">
                <a href="/register">REGISTER</a>
            </button>
        </div>

        <!-- Wachtwoord vergeten link -->
        @if (Route::has('password.request'))
            <div class="mt-4 text-center">
                <a href="{{ route('password.request') }}"
                   class="text-sm text-gray-500 hover:underline">
                    Wachtwoord vergeten?
                </a>
            </div>
        @endif
    </form>

</x-guest-layout>
