@php
    // Pick a subtle accent color based on the current user's role, so the
    // active nav tab / avatar gives a quick visual hint of who is logged
    // in. Priority order matters when a user has more than one role.
    // The base brand orange (#da532c) is kept for admin and as the
    // fallback, so the look barely changes for most users.
    $accentColor = match (true) {
        Auth::user()->hasRole('admin') => '#da532c', // brand orange
        Auth::user()->hasRole('manager') => '#2c6da5', // blue
        Auth::user()->hasRole('receptionist') => '#2c9e6f', // green
        Auth::user()->hasRole('orderpicker') => '#8b5cf6', // purple
        default => '#da532c', // fallback, shouldn't normally happen — every account has a role
    };

    // Count of out-of-stock items still owed a refund — shown as a small
    // badge on the "Terugbetalingen" nav link so receptionist/manager/admin
    // notice there's an open task without having to open the page first.
    // Only computed for roles that can actually see that link.
    $pendingRefundsCount = Auth::user()->hasAnyRole(['receptionist', 'admin', 'manager'])
        ? \App\Models\OrderItem::whereNotNull('out_of_stock_at')->whereNull('refunded_at')->count()
        : 0;
@endphp
@php
    // Number of customers who still owe money — same audience-gating as
    // above. Kept as a lightweight count query rather than reusing
    // DebtController's full logic, since the nav just needs a badge.
    $debtorsCount = 0;
    if (Auth::user()->hasAnyRole(['receptionist', 'admin', 'manager'])) {
        $debtorsCount = \App\Models\Order::whereNotNull('customer_id')
            ->where('paid', false)
            ->whereNotIn('status', ['cancelled', 'refused'])
            ->with('items')
            ->get()
            ->filter(fn ($order) => $order->outstandingBalance() > 0)
            ->pluck('customer_id')
            ->unique()
            ->count();
    }
@endphp
<nav x-data="{ open: false }" style="background-color: #1e2235;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Left: logo + nav links --}}
            <div class="flex items-center">

                <a href="{{ route('orders.index') }}" class="flex items-center space-x-2 mr-8">
                    <span style="color: #da532c; font-size: 1.6rem; font-weight: 800; letter-spacing: -1px; line-height: 1;">
                        i<span style="color: white;">8</span>c
                    </span>
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden sm:flex space-x-1">
                    <a href="{{ route('orders.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs('orders.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                       style="{{ request()->routeIs('orders.*') ? 'background-color: '.$accentColor.';' : '' }}">
                        Bestellingen
                    </a>
                    {{-- Customer management is a receptionist/admin/manager job — orderpicker doesn't get this link --}}
                    @hasanyrole('receptionist|admin|manager')
                        <a href="{{ route('customers.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition
                                  {{ request()->routeIs('customers.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                           style="{{ request()->routeIs('customers.*') ? 'background-color: '.$accentColor.';' : '' }}">
                            Klanten
                        </a>
                    @endhasanyrole
                    {{-- Product catalog — same visibility as customers: receptionist/admin/manager --}}
                    @hasanyrole('receptionist|admin|manager')
                        <a href="{{ route('products.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition
                                  {{ request()->routeIs('products.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                           style="{{ request()->routeIs('products.*') ? 'background-color: '.$accentColor.';' : '' }}">
                            Producten
                        </a>
                    @endhasanyrole
                    {{-- Refunds owed for out-of-stock items — same audience as
                         Klanten/Producten above --}}
                    @hasanyrole('receptionist|admin|manager')
                        <a href="{{ route('refunds.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-1.5
                                  {{ request()->routeIs('refunds.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                           style="{{ request()->routeIs('refunds.*') ? 'background-color: '.$accentColor.';' : '' }}">
                            Terugbetalingen
                            @if ($pendingRefundsCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-bold bg-orange-500 text-white">
                                    {{ $pendingRefundsCount }}
                                </span>
                            @endif
                        </a>
                    @endhasanyrole
                    {{-- Debts owed to us — the flip side of Terugbetalingen above --}}
                    @hasanyrole('receptionist|admin|manager')
                        <a href="{{ route('debts.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-1.5
                                  {{ request()->routeIs('debts.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                           style="{{ request()->routeIs('debts.*') ? 'background-color: '.$accentColor.';' : '' }}">
                            Schulden
                            @if ($debtorsCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-bold bg-red-500 text-white">
                                    {{ $debtorsCount }}
                                </span>
                            @endif
                        </a>
                    @endhasanyrole
                    {{-- Viewing/editing is also open to manager — creating/deleting stays admin-only --}}
                    @hasanyrole('admin|manager')
                        <a href="{{ route('admin.users.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition
                                  {{ request()->routeIs('admin.users.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                           style="{{ request()->routeIs('admin.users.*') ? 'background-color: '.$accentColor.';' : '' }}">
                            Gebruikers
                        </a>
                    @endhasanyrole
                    {{-- Dashboard — last, it's a summary/shortcut, not a primary work tab --}}
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                       style="{{ request()->routeIs('dashboard') ? 'background-color: '.$accentColor.';' : '' }}">
                        Dashboard
                    </a>
                </div>
            </div>

            {{-- Right: user dropdown --}}
            <div class="hidden sm:flex items-center">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center space-x-2 text-sm text-gray-300 hover:text-white transition px-3 py-2 rounded-lg hover:bg-white/10">
                        {{-- User avatar circle — tinted with the role accent color --}}
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                              style="background-color: {{ $accentColor }};">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span>{{ Auth::user()->name }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown menu --}}
                    <div x-show="open" @click.outside="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Profiel
                        </a>
                        <hr class="my-1 border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                Afmelden
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile hamburger --}}
            <div class="flex items-center sm:hidden">
                <button @click="open = !open" class="text-gray-400 hover:text-white p-2 rounded-lg">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden px-4 pb-4 space-y-1">
        <a href="{{ route('orders.index') }}"
           class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
            Bestellingen
        </a>
        @hasanyrole('receptionist|admin|manager')
            <a href="{{ route('customers.index') }}"
               class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
                Klanten
            </a>
        @endhasanyrole
        @hasanyrole('receptionist|admin|manager')
            <a href="{{ route('products.index') }}"
               class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
                Producten
            </a>
        @endhasanyrole
        @hasanyrole('receptionist|admin|manager')
            <a href="{{ route('refunds.index') }}"
               class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
                Terugbetalingen
                @if ($pendingRefundsCount > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-bold bg-orange-500 text-white">
                        {{ $pendingRefundsCount }}
                    </span>
                @endif
            </a>
        @endhasanyrole
        @hasanyrole('receptionist|admin|manager')
            <a href="{{ route('debts.index') }}"
               class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
                Schulden
                @if ($debtorsCount > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[11px] font-bold bg-red-500 text-white">
                        {{ $debtorsCount }}
                    </span>
                @endif
            </a>
        @endhasanyrole
        {{-- Viewing/editing is also open to manager — creating/deleting stays admin-only --}}
        @hasanyrole('admin|manager')
            <a href="{{ route('admin.users.index') }}"
               class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
                Gebruikers
            </a>
        @endhasanyrole
        <a href="{{ route('dashboard') }}"
           class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
            Dashboard
        </a>
        <hr class="border-white/10 my-2">
        <a href="{{ route('profile.edit') }}"
           class="block px-4 py-2 rounded-lg text-sm text-gray-300 hover:text-white hover:bg-white/10">
            Profiel
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full text-left px-4 py-2 rounded-lg text-sm text-red-400 hover:bg-white/10">
                Afmelden
            </button>
        </form>
    </div>
</nav>
