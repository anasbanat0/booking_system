@extends('layouts.app')

@section('content')
<div class="min-h-screen overflow-x-hidden bg-[#eef2f0]">
    <main class="mx-auto w-full max-w-5xl px-3 py-5 sm:px-6 sm:py-8 lg:px-8">
        <section class="overflow-hidden rounded-xl border border-white bg-white shadow-xl shadow-slate-900/10">
            <div class="arabic-instructions-font bg-[#071817] px-4 py-8 text-center text-white sm:px-8 sm:py-10">
                <p dir="rtl" class="mx-auto max-w-full break-words text-xs font-extrabold uppercase tracking-[0.18em] text-teal-200 sm:text-sm sm:tracking-[0.24em]">
                    {{ $instructionsHeroEyebrow }}
                </p>
                <h1 dir="rtl" class="instructions-hero-title mx-auto mt-4 max-w-4xl break-words text-3xl font-black leading-tight tracking-tight sm:text-4xl lg:text-5xl">
                    {{ $instructionsHeroTitle }}
                </h1>
                <p dir="rtl" class="instructions-hero-description mx-auto mt-5 max-w-3xl break-words text-base font-semibold leading-8 text-white/78 sm:text-lg sm:leading-9">
                    {{ $instructionsHeroDescription }}
                </p>
            </div>

            <div class="p-4 sm:p-8">
                <div dir="rtl" class="arabic-instructions-font instructions-content prose max-w-none text-right leading-9 text-slate-900 prose-headings:text-slate-950 prose-strong:text-slate-950">
                    {!! $instructionsAr !!}
                </div>
            </div>
        </section>
    </main>
</div>
@endsection
