<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-6 lg:px-8">

            @if ($isPureOrderpicker)
                {{-- Orderpicker: only what's relevant to picking orders --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <a href="{{ route('orders.index', ['status' => 'sent']) }}" class="bg-orange-50 border border-orange-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Te verwerken</p>
                        <p class="text-3xl font-semibold text-orange-600 mt-1">{{ $toPick }}</p>
                        <p class="text-xs text-gray-400 mt-1">bestellingen bij de orderpicker</p>
                    </a>
                    <a href="{{ route('orders.index', ['status' => 'sent', 'mine' => 1]) }}" class="bg-purple-50 border border-purple-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">In behandeling door mij</p>
                        <p class="text-3xl font-semibold text-purple-600 mt-1">{{ $inProgress }}</p>
                        <p class="text-xs text-gray-400 mt-1">overgenomen, nog niet klaar</p>
                    </a>
                    <a href="{{ route('orders.index', ['status' => 'ready_for_pickup']) }}" class="bg-green-50 border border-green-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Klaar om op te halen</p>
                        <p class="text-3xl font-semibold text-green-600 mt-1">{{ $readyForPickup }}</p>
                        <p class="text-xs text-gray-400 mt-1">wachten op de klant</p>
                    </a>
                </div>
            @else
                {{-- Receptionist/admin/manager: full operational overview --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="{{ route('orders.index', ['status' => 'awaiting_review,failed']) }}" class="bg-orange-50 border border-orange-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Vraagt aandacht</p>
                        <p class="text-3xl font-semibold text-orange-600 mt-1">
                            {{ $needsAttention }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">wacht op validatie of mislukt</p>
                    </a>
                    <a href="{{ route('orders.index', ['status' => 'sent']) }}" class="bg-purple-50 border border-purple-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Bij de orderpicker</p>
                        <p class="text-3xl font-semibold text-purple-600 mt-1">{{ $atOrderpicker }}</p>
                        <p class="text-xs text-gray-400 mt-1">nog niet klaargemaakt</p>
                    </a>
                    <a href="{{ route('orders.index', ['status' => 'ready_for_pickup']) }}" class="bg-green-50 border border-green-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Klaar om op te halen</p>
                        <p class="text-3xl font-semibold text-green-600 mt-1">{{ $readyForPickup }}</p>
                        <p class="text-xs text-gray-400 mt-1">wachten op de klant</p>
                    </a>
                    <a href="{{ route('debts.index') }}" class="bg-red-50 border border-red-100 rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Openstaande schulden</p>
                        <p class="text-3xl font-semibold text-red-600 mt-1">
                            € {{ number_format($debtorsTotal, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">nog te ontvangen van klanten</p>
                    </a>
                    <a href="{{ route('refunds.index') }}" class="bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Terug te betalen</p>
                        <p class="text-3xl font-semibold {{ $refundsTotal > 0 ? 'text-orange-700' : 'text-gray-900' }} mt-1">
                            € {{ number_format($refundsTotal, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">niet meer op voorraad</p>
                    </a>
                    @if (! is_null($productsCount))
                        <a href="{{ route('products.index') }}" class="bg-blue-50 border border-blue-100 rounded-lg p-6 hover:shadow-md transition">
                            <p class="text-sm text-gray-500">Producten</p>
                            <p class="text-3xl font-semibold text-blue-600 mt-1">{{ $productsCount }}</p>
                            <p class="text-xs text-gray-400 mt-1">in de catalogus</p>
                        </a>
                    @endif
                    @if (! is_null($usersCount))
                        <a href="{{ route('admin.users.index') }}" class="bg-slate-200 border border-slate-300 rounded-lg p-6 hover:shadow-md transition">
                            <p class="text-sm text-gray-500">Gebruikers</p>
                            <p class="text-3xl font-semibold text-slate-700 mt-1">{{ $usersCount }}</p>
                            <p class="text-xs text-gray-400 mt-1">accounts in het systeem</p>
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
