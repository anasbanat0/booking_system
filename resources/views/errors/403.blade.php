<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-950">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-bold uppercase tracking-wide text-amber-700">403 Access Denied</p>
            <h1 class="mt-3 text-3xl font-extrabold">You do not have permission to open this page.</h1>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Your account may be limited to a student area or a specific branch. Please return to your panel or contact the main admin if this looks wrong.
            </p>
            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/') }}" class="rounded-md bg-slate-950 px-5 py-3 text-center text-sm font-extrabold text-white hover:bg-slate-800">Homepage</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md border border-slate-300 px-5 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-100">Open my panel</a>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
