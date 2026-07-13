{{--
    Reusable red/translucent delete-confirmation alert — same visual style
    as the payment confirmation alert (see orders/index.blade.php and
    orders/show.blade.php), used instead of the native browser confirm()
    popup for a generic "are you sure you want to delete this?" action.

    Expects an ancestor element with Alpine state:
        x-data="{ deleteModalOpen: false, deleteForm: null }"
    Each delete button should trigger it like this, instead of the old
    onsubmit="return confirm(...)":
        <button type="button"
                @click="deleteForm = $el.closest('form'); deleteModalOpen = true">
            Verwijderen
        </button>
--}}
<div x-show="deleteModalOpen"
     x-cloak
     style="display: none;"
     @click.self="deleteModalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 px-4">
    <div x-show="deleteModalOpen"
         x-transition
         class="w-full max-w-sm rounded-xl border border-red-300 bg-red-100/80 backdrop-blur-sm p-6 shadow-lg">
        <p class="text-sm font-medium text-red-800">
            {{ $message ?? 'Weet je zeker dat je dit wil verwijderen? Dit kan niet ongedaan gemaakt worden.' }}
        </p>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button"
                    @click="deleteModalOpen = false"
                    class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                Annuleren
            </button>
            <button type="button"
                    @click="deleteForm.submit(); deleteModalOpen = false"
                    class="px-3 py-1.5 rounded text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition">
                Verwijderen
            </button>
        </div>
    </div>
</div>
