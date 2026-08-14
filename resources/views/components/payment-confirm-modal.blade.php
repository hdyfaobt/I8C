{{--
    Reusable payment confirmation alert — replaces the native browser
    confirm() popup with a translucent, colored alert that matches the
    site's look: green while confirming a payment (asks for the payment
    method too), orange while undoing one. Used by orders/index.blade.php
    (every row's "Betaling" button) and orders/show.blade.php.

    Expects an ancestor element with Alpine state:
        x-data="{ paymentModalOpen: false, paymentForm: null, paymentMode: 'pay', paying: false }"
    Each trigger button should set it like this:
        @click="paymentForm = $el.closest('form'); paymentMode = 'pay'; paymentModalOpen = true"
--}}
<div x-show="paymentModalOpen"
     x-cloak
     style="display: none;"
     @click.self="paymentModalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 px-4">
    <div x-show="paymentModalOpen"
         x-transition
         class="w-full max-w-sm rounded-xl border p-6 shadow-lg backdrop-blur-sm"
         :class="paymentMode === 'pay'
            ? 'bg-green-100/80 border-green-300'
            : 'bg-orange-100/80 border-orange-300'">

        {{-- Marking as paid — pick a payment method first --}}
        <template x-if="paymentMode === 'pay'">
            <div>
                <p class="text-sm font-medium text-green-800">
                    Bevestig: hoe werd deze bestelling betaald?
                </p>
                <div class="mt-4 flex flex-col gap-2">
                    <button type="button"
                            :disabled="paying"
                            @click="paying = true; paymentForm.querySelector('[name=payment_method]').value = 'bank_transfer'; paymentForm.requestSubmit()"
                            class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition disabled:opacity-50">
                        <span x-show="!paying">Overschrijving / Bancontact</span>
                        <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                    </button>
                    <button type="button"
                            :disabled="paying"
                            @click="paying = true; paymentForm.querySelector('[name=payment_method]').value = 'cash'; paymentForm.requestSubmit()"
                            class="px-3 py-2 rounded text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition disabled:opacity-50">
                        <span x-show="!paying">Cash</span>
                        <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                    </button>
                </div>
                <div class="mt-3 flex justify-end" x-show="!paying">
                    <button type="button"
                            @click="paymentModalOpen = false"
                            class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                        Annuleren
                    </button>
                </div>
            </div>
        </template>

        {{-- Undoing a payment — admin/manager only, plain confirm --}}
        <template x-if="paymentMode === 'revert'">
            <div>
                <p class="text-sm font-medium text-orange-800">
                    Bevestig: deze bestelling markeren als NIET betaald?
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button"
                            x-show="!paying"
                            @click="paymentModalOpen = false"
                            class="px-3 py-1.5 rounded text-xs font-medium bg-white text-gray-700 hover:bg-gray-50 transition border border-gray-300">
                        Annuleren
                    </button>
                    <button type="button"
                            :disabled="paying"
                            @click="paying = true; paymentForm.requestSubmit()"
                            class="px-3 py-1.5 rounded text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 transition disabled:opacity-50">
                        <span x-show="!paying">Bevestigen</span>
                        <span x-show="paying" x-cloak style="display: none;">Bezig...</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
