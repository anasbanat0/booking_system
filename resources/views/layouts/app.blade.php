<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $siteTitle = \App\Models\SiteContent::getValue('page_title', 'Samir Medical Hub') ?: 'Samir Medical Hub';
            $routeTitles = [
                'calendar.index' => 'Calendar',
                'bookings.my' => 'My Bookings',
                'instructions' => 'Instructions',
                'admin.dashboard' => 'Dashboard',
                'admin.bookings.index' => 'Bookings',
                'admin.users-calendar.index' => 'Users Calendar',
                'admin.notifications.index' => 'Notifications',
                'admin.slots.index' => 'Slots Time',
                'admin.manage.users.index' => 'Manage Users',
                'admin.content.index' => 'Homepage',
                'admin.activity.index' => 'Activity Log',
                'admin.booking-rules.update' => 'Booking Rules',
                'profile.edit' => 'Profile',
            ];
            $pageTitle = $routeTitles[request()->route()?->getName()] ?? null;
        @endphp
        <title>{{ $pageTitle ? $pageTitle . ' - ' . $siteTitle : $siteTitle }}</title>

        <script>
            (() => {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const useDark = storedTheme ? storedTheme === 'dark' : prefersDark;

                document.documentElement.classList.toggle('dark', useDark);
                document.documentElement.style.colorScheme = useDark ? 'dark' : 'light';
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|cairo:400,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-toast-notifications />

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
