@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#eef2f0]">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-xl border border-white bg-white shadow-xl shadow-slate-900/10">
            <div class="bg-[#071817] px-6 py-8 text-white sm:px-8">
                <p class="text-sm font-extrabold uppercase tracking-[0.24em] text-teal-200">Samir Foundation Medical Hub</p>
                <h1 class="mt-4 text-4xl font-black tracking-tight lg:text-5xl">Booking instructions</h1>
                <p class="mt-4 max-w-3xl text-base leading-8 text-white/72">
                    A quiet, equipped space for students who need reliable electricity, internet access, and a focused environment for studying or online exams.
                </p>
            </div>

            <div class="p-6 sm:p-8">
                <div class="prose max-w-none text-slate-900 leading-9 prose-headings:text-slate-950 prose-strong:text-slate-950">
                    {!! $instructionsEn !!}
                </div>

            </div>
        </section>
    </main>
</div>
@endsection
