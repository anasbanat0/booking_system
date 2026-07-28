<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 dark:border-slate-800 dark:bg-slate-950">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-3 sm:px-5 lg:px-8">
        <div class="flex h-16 justify-between sm:h-20 lg:h-24">
            <div class="flex min-w-0">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="logo-surface inline-flex max-w-[12rem] items-center overflow-hidden rounded-2xl border border-white bg-white px-2 py-1.5 shadow-sm shadow-slate-900/5 transition dark:border-white dark:shadow-lg dark:shadow-sky-950/30 sm:max-w-[19rem] lg:max-w-[24rem]">
                        <x-application-logo class="block h-9 w-full object-contain fill-current text-gray-800 sm:h-11 lg:h-12" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if(Auth::user()?->role === 'student')
                        <x-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.index')">
                            {{ __('Calendar') }}
                        </x-nav-link>
                        <x-nav-link :href="route('bookings.my')" :active="request()->routeIs('bookings.my')">
                            {{ __('My Bookings') }}
                        </x-nav-link>
                        <x-nav-link :href="route('instructions')" :active="request()->routeIs('instructions')">
                            {{ __('Instructions') }}
                        </x-nav-link>
                    @elseif(Auth::user()?->isAdminPanelUser())
                        <x-nav-link :href="route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.users-calendar.index')" :active="request()->routeIs('admin.*')">
                            {{ Auth::user()->role === 'admin' ? __('Admin Panel') : __('Staff Panel') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="me-3">
                    <x-theme-toggle />
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 dark:bg-slate-950 dark:text-slate-300 dark:hover:text-white">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-2 sm:hidden">
                <x-theme-toggle />

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if(Auth::user()?->role === 'student')
                <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.index')">
                    {{ __('Calendar') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bookings.my')" :active="request()->routeIs('bookings.my')">
                    {{ __('My Bookings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('instructions')" :active="request()->routeIs('instructions')">
                    {{ __('Instructions') }}
                </x-responsive-nav-link>
            @elseif(Auth::user()?->isAdminPanelUser())
                <x-responsive-nav-link :href="route(Auth::user()->role === 'admin' ? 'admin.dashboard' : 'admin.users-calendar.index')" :active="request()->routeIs('admin.*')">
                    {{ Auth::user()->role === 'admin' ? __('Admin Panel') : __('Staff Panel') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-slate-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-slate-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
