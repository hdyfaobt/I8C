{{--
    Account deletion — same translucent red confirmation style used
    everywhere else on the site (see x-confirm-delete-modal), but with its
    own markup here since this one needs a password field, not just a plain
    yes/no. Reopens automatically if the password confirmation failed (see
    the userDeletion error bag from ProfileController::destroy()).
--}}
<div x-data="{ deleteAccountModalOpen: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <h3 class="text-lg font-medium text-gray-900">Account verwijderen</h3>
    <p class="text-sm text-gray-500 mt-1">
        Eens je account verwijderd is, worden alle bijhorende gegevens definitief verwijderd. Download eerst alle gegevens die je wil behouden.
    </p>

    <button type="button"
            @click="deleteAccountModalOpen = true"
            class="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm">
        Account verwijderen
    </button>

    <div x-show="deleteAccountModalOpen"
         x-cloak
         style="display: none;"
         @click.self="deleteAccountModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 px-4">
        <div x-show="deleteAccountModalOpen"
             x-transition
             class="w-full max-w-sm rounded-xl border border-red-300 bg-red-100/80 backdrop-blur-sm p-6 shadow-lg">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <p class="text-sm font-medium text-red-800">
                    Weet je zeker dat je je account wil verwijderen? Dit kan niet ongedaan gemaakt worden.
                </p>

                <div class="mt-4">
                    <input type="password" name="password" placeholder="Wachtwoord"
                           class="w-full rounded-md border-2 border-red-200 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition">
                    @error('password', 'userDeletion')
                        <p class="text-red-700 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button"
                            @click="deleteAccountModalOpen = false"
                            class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                        Annuleren
                    </button>
                    <button type="submit"
                            class="px-3 py-1.5 rounded text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition">
                        Account verwijderen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
