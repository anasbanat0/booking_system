@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
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

            <form id="content-editor-form" method="POST" action="{{ route('admin.content.update') }}" class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
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

                <div class="grid gap-5 lg:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Student instructions - English</span>
                        <input type="hidden" name="content[instructions_en]" value="{{ old('content.instructions_en', $contents['instructions_en']->value ?? '') }}" data-editor-input="instructions-en">
                        <div class="mt-2 flex gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2">
                            <button type="button" data-editor-command="bold" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">B</button>
                            <button type="button" data-editor-command="insertUnorderedList" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">List</button>
                        </div>
                        <div id="instructions-en" contenteditable="true" class="min-h-48 rounded-b-md border border-slate-300 bg-white p-3 text-sm leading-6 focus:outline-none focus:ring-2 focus:ring-blue-500">{!! old('content.instructions_en', $contents['instructions_en']->value ?? '<p>Read the booking rules carefully before choosing a weekly slot.</p>') !!}</div>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Student instructions - Arabic</span>
                        <input type="hidden" name="content[instructions_ar]" value="{{ old('content.instructions_ar', $contents['instructions_ar']->value ?? '') }}" data-editor-input="instructions-ar">
                        <div class="mt-2 flex gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2">
                            <button type="button" data-editor-command="bold" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">B</button>
                            <button type="button" data-editor-command="insertUnorderedList" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">List</button>
                        </div>
                        <div id="instructions-ar" contenteditable="true" dir="rtl" class="min-h-48 rounded-b-md border border-slate-300 bg-white p-3 text-right text-sm leading-6 focus:outline-none focus:ring-2 focus:ring-blue-500">{!! old('content.instructions_ar', $contents['instructions_ar']->value ?? '<p>اقرأ تعليمات الحجز قبل اختيار الموعد.</p>') !!}</div>
                    </label>
                </div>

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

<script>
document.querySelectorAll('[data-editor-command]').forEach(button => {
    button.addEventListener('click', () => {
        const editor = document.getElementById(button.dataset.editorTarget);
        editor.focus();
        document.execCommand(button.dataset.editorCommand, false, null);
    });
});

document.getElementById('content-editor-form')?.addEventListener('submit', () => {
    document.querySelectorAll('[data-editor-input]').forEach(input => {
        const editor = document.getElementById(input.dataset.editorInput);
        input.value = editor?.innerHTML || '';
    });
});
</script>
@endsection
