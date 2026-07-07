@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Student Interface</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Homepage Content</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    Update the introductory text, usage instructions, supporters, contact info, and social links shown to students.
                </p>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.content.update') }}" class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Project intro</span>
                    <textarea name="content[project_intro]" rows="4"
                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.project_intro', $contents['project_intro']->value ?? '') }}</textarea>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Usage instructions</span>
                    <textarea name="content[usage_instructions]" rows="6"
                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.usage_instructions', $contents['usage_instructions']->value ?? '') }}</textarea>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Supporters</span>
                    <input name="content[supporters]" value="{{ old('content.supporters', $contents['supporters']->value ?? '') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="mt-1 block text-xs text-slate-500">Separate names with commas.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Contact info</span>
                    <textarea name="content[contact_info]" rows="4"
                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.contact_info', $contents['contact_info']->value ?? '') }}</textarea>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Social links</span>
                    <textarea name="content[social_links]" rows="4"
                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.social_links', $contents['social_links']->value ?? '') }}</textarea>
                    <span class="mt-1 block text-xs text-slate-500">Use one link per line, for example: Facebook: https://facebook.com</span>
                </label>

                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Save content
                </button>
            </form>
        </div>
    </main>
</div>
@endsection
