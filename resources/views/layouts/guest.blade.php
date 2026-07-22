<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-toast-notifications />

        <div class="min-h-screen bg-[#eef2f0] px-4 py-8">
            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
                <div class="grid w-full gap-8 lg:grid-cols-[1fr_520px] lg:items-center">
                    <section class="hidden lg:block">
                        <a href="/" class="inline-flex items-center rounded-[1.4rem] border border-white/80 bg-white/85 px-4 py-3 shadow-xl shadow-slate-900/10">
                            <x-application-logo class="h-14 w-auto" />
                        </a>
                        <p class="mt-10 text-sm font-extrabold uppercase tracking-[0.28em] text-teal-700">Samir Foundation</p>
                        <h1 class="mt-4 max-w-2xl text-6xl font-black leading-[0.95] text-slate-950">
                            Medical Hub for focused study and online exams.
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                            A calm student space prepared with dependable electricity, internet access, and a clear booking flow for weekly seats.
                        </p>
                        <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
                            <div class="rounded-lg border border-white bg-white/70 p-4 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Power</p>
                                <p class="mt-2 text-sm font-extrabold text-slate-950">Reliable setup</p>
                            </div>
                            <div class="rounded-lg border border-white bg-white/70 p-4 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Internet</p>
                                <p class="mt-2 text-sm font-extrabold text-slate-950">Study ready</p>
                            </div>
                            <div class="rounded-lg border border-white bg-white/70 p-4 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Branches</p>
                                <p class="mt-2 text-sm font-extrabold text-slate-950">Gaza and Khan Younis</p>
                            </div>
                        </div>
                    </section>

                    <div>
                        <div class="mb-5 flex justify-center lg:hidden">
                            <a href="/" class="rounded-[1.2rem] border border-white/80 bg-white/85 px-4 py-3 shadow-lg shadow-slate-900/10">
                                <x-application-logo class="h-12 w-auto" />
                            </a>
                        </div>

            <div class="w-full overflow-hidden rounded-xl border border-white/80 bg-white/95 px-6 py-6 shadow-2xl shadow-slate-900/10 sm:px-8">
                {{ $slot }}
            </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
