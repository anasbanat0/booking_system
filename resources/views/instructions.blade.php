@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#f6f7f4]">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-lg bg-stone-950 p-8 text-white shadow-xl shadow-stone-300/40">
            <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Student dashboard</p>
            <h1 class="mt-4 text-4xl font-extrabold lg:text-5xl">Booking instructions</h1>
            <p class="mt-4 max-w-3xl text-base leading-8 text-white/70">
                Review these notes before booking, cancelling, or rescheduling your weekly appointment.
            </p>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-extrabold">English</h2>
                <div class="prose mt-5 max-w-none text-stone-700">
                    {!! $instructionsEn !!}
                </div>
            </section>

            <section class="rounded-lg border border-stone-200 bg-white p-6 text-right shadow-sm" dir="rtl">
                <h2 class="text-2xl font-extrabold">العربية</h2>
                <div class="prose mt-5 max-w-none text-stone-700">
                    {!! $instructionsAr !!}
                </div>
            </section>
        </div>
    </main>
</div>
@endsection
