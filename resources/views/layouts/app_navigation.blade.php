<nav x-data="{ open: false }" style="background-color: #1e2235;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- Left: logo + nav links --}}
            <div class="flex items-center">

                {{-- I8C Logo (text-based, no Laravel SVG) --}}
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 mr-8">
                    <span style="color: #da532c; font-size: 1.6rem; font-weight: 800; letter-spacing: -1px; line-height: 1;">
                        i<span style="color: white;">8</span>c
                    </span>
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden sm:flex space-x-1">
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                       style="{{ request()->routeIs('dashboard') ? 'background-color: #da532c;' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('customers.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs('customers.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                       style="{{ request()->routeIs('customers.*') ? 'background-color: #da532c;' : '' }}">
                        Klanten
                    </a>
                    <a href="{{ route('orders.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs('orders.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-white/10' }}"
                       style="{{ request()->routeIs('orders.*') ? 'background-color: #da532c;' : '' }}">
                        Bestellingen
                    </a>
                </div>
            </div>

            {{-- Right: user dropdown --}}
            <div class="hidden sm:flex items-center">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center space-x-2 text-sm text-gray-300 hover:text-white transition px-3 py-2 rounded-lg hover:bg-white/10">
                        {{-- User avatar circle --}}
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                              style="background-color: #da532c;">
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
        <a href="{{ route('dashboard') }}"
           class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
            Dashboard
        </a>
        <a href="{{ route('customers.index') }}"
           class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
            Klanten
        </a>
        <a href="{{ route('orders.index') }}"
           class="block px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-white/10">
            Bestellingen
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
