@extends('layouts.app')

@section('content')
@php
    $requestedHub = request('hub');
    $selectedForm = $forms->first(fn ($item) => $item->location?->slug === $requestedHub) ?? $forms->first();
@endphp
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')
    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Registration setup</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Registration Forms</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Edit public copy, fields, choices, and required status. Changes appear on the registration page immediately.</p>
            </div>

            @if(session('success'))<div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">Please correct the highlighted form values and try again.</div>@endif

            <nav class="mt-6 flex gap-2 border-b border-slate-200" aria-label="Registration form branches">
                @foreach($forms as $item)
                    <a href="{{ route('admin.hub-registration-forms.index', ['hub' => $item->location->slug]) }}" class="border-b-2 px-4 py-3 text-sm font-bold {{ $selectedForm?->id === $item->id ? 'border-emerald-700 text-emerald-800' : 'border-transparent text-slate-500 hover:text-slate-900' }}">{{ $item->location->name }} Hub</a>
                @endforeach
            </nav>

            @if($selectedForm)
                <form id="hub-registration-form-editor" method="POST" action="{{ route('admin.hub-registration-forms.update', $selectedForm->location) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-4">
                            <div><h2 class="text-lg font-bold text-slate-950">Public page content</h2><p class="mt-1 text-sm text-slate-500">All visitor-facing fields are in English.</p></div>
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $selectedForm->is_active)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">Form active</label>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <label class="block"><span class="text-sm font-bold text-slate-700">Eyebrow</span><input name="eyebrow" value="{{ old('eyebrow', $selectedForm->eyebrow) }}" class="mt-1 block h-11 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block"><span class="text-sm font-bold text-slate-700">Page title</span><input name="title" value="{{ old('title', $selectedForm->title) }}" class="mt-1 block h-11 w-full rounded-md border-slate-300 text-sm"></label>
                            @foreach([
                                ['name' => 'description', 'label' => 'Description', 'height' => 'min-h-32'],
                                ['name' => 'instructions', 'label' => 'Instructions', 'height' => 'min-h-40'],
                            ] as $richField)
                                @php
                                    $richValue = old($richField['name'], $selectedForm->{$richField['name']});
                                    $editorId = 'registration-' . $richField['name'];
                                @endphp
                                <div class="block md:col-span-2">
                                    <span class="text-sm font-bold text-slate-700">{{ $richField['label'] }}</span>
                                    <input type="hidden" name="{{ $richField['name'] }}" value="{{ $richValue }}" data-rich-input="{{ $editorId }}">
                                    <div class="mt-1 flex flex-wrap gap-1 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2" role="toolbar" aria-label="{{ $richField['label'] }} formatting">
                                        <button type="button" data-rich-format="p" data-rich-target="{{ $editorId }}" class="h-8 min-w-9 rounded border border-slate-300 bg-white px-2 text-xs font-bold text-slate-700 hover:bg-slate-100" title="Paragraph">P</button>
                                        <button type="button" data-rich-format="h2" data-rich-target="{{ $editorId }}" class="h-8 min-w-9 rounded border border-slate-300 bg-white px-2 text-xs font-bold text-slate-700 hover:bg-slate-100" title="Heading">H2</button>
                                        <button type="button" data-rich-command="bold" data-rich-target="{{ $editorId }}" class="h-8 min-w-9 rounded border border-slate-300 bg-white px-2 text-sm font-black text-slate-900 hover:bg-slate-100" title="Bold">B</button>
                                        <button type="button" data-rich-command="italic" data-rich-target="{{ $editorId }}" class="h-8 min-w-9 rounded border border-slate-300 bg-white px-2 text-sm font-bold italic text-slate-900 hover:bg-slate-100" title="Italic">I</button>
                                        <button type="button" data-rich-command="insertUnorderedList" data-rich-target="{{ $editorId }}" class="h-8 rounded border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-100" title="Bulleted list">&#8226; List</button>
                                        <button type="button" data-rich-command="insertOrderedList" data-rich-target="{{ $editorId }}" class="h-8 rounded border border-slate-300 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-100" title="Numbered list">1. List</button>
                                    </div>
                                    <div id="{{ $editorId }}" contenteditable="true" role="textbox" aria-multiline="true" class="hub-rich-editor hub-rich-text {{ $richField['height'] }} rounded-b-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600">{!! \App\Support\SafeRichText::render($richValue) !!}</div>
                                </div>
                            @endforeach
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Hub instructions PDF</span>
                                    <span class="mt-1 block text-xs text-slate-500">Upload the guide shown to applicants for this hub. PDF only, maximum 20 MB.</span>
                                    <input type="file" name="instructions_pdf" accept="application/pdf,.pdf" class="mt-3 block w-full rounded-md border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-slate-900 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white">
                                </label>
                                @if($selectedForm->instructions_file_path)
                                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                                        <a href="{{ route('hub-registration.instructions-file', $selectedForm->location) }}" target="_blank" rel="noopener" class="font-bold text-blue-700 hover:underline">Open {{ $selectedForm->instructions_file_name }}</a>
                                        <label class="flex items-center gap-2 font-bold text-rose-700"><input type="checkbox" name="remove_instructions_pdf" value="1" class="rounded border-slate-300 text-rose-600">Remove current PDF</label>
                                    </div>
                                @endif
                            </div>
                            <label class="block"><span class="text-sm font-bold text-slate-700">Submit button</span><input name="submit_button" value="{{ old('submit_button', $selectedForm->submit_button) }}" class="mt-1 block h-11 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block"><span class="text-sm font-bold text-slate-700">Success title</span><input name="success_title" value="{{ old('success_title', $selectedForm->success_title) }}" class="mt-1 block h-11 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="block md:col-span-2"><span class="text-sm font-bold text-slate-700">Success message</span><textarea name="success_message" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm">{{ old('success_message', $selectedForm->success_message) }}</textarea></label>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-lg font-bold text-slate-950">Form fields</h2><p class="mt-1 text-sm text-slate-500">One choice per line for radio and select fields. Core identity fields remain active.</p></div>
                        <div class="divide-y divide-slate-200">
                            @foreach($selectedForm->fields as $index => $field)
                                @php
                                    $isCore = in_array($field->key, $coreFields, true);
                                @endphp
                                <div class="p-5">
                                    <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field->id }}">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div><span class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $field->key }}</span><h3 class="mt-1 font-bold text-slate-900">{{ $field->label }}</h3></div>
                                        <div class="flex gap-4 text-sm font-bold text-slate-600">
                                            <label class="flex items-center gap-2"><input type="checkbox" name="fields[{{ $index }}][is_required]" value="1" @checked(old("fields.$index.is_required", $field->is_required)) @disabled($isCore) class="rounded border-slate-300 text-emerald-700">Required</label>
                                            <label class="flex items-center gap-2"><input type="checkbox" name="fields[{{ $index }}][is_active]" value="1" @checked(old("fields.$index.is_active", $field->is_active)) @disabled($isCore) class="rounded border-slate-300 text-emerald-700">Active</label>
                                        </div>
                                    </div>
                                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                        <label><span class="text-xs font-bold text-slate-600">Label</span><input name="fields[{{ $index }}][label]" value="{{ old("fields.$index.label", $field->label) }}" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                                        <label><span class="text-xs font-bold text-slate-600">Type</span><select name="fields[{{ $index }}][type]" @disabled($isCore) class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm">@foreach($fieldTypes as $type)<option value="{{ $type }}" @selected($field->type === $type)>{{ ucfirst($type) }}</option>@endforeach</select>@if($isCore)<input type="hidden" name="fields[{{ $index }}][type]" value="{{ $field->type }}">@endif</label>
                                        <label><span class="text-xs font-bold text-slate-600">Placeholder</span><input name="fields[{{ $index }}][placeholder]" value="{{ old("fields.$index.placeholder", $field->placeholder) }}" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                                        <label><span class="text-xs font-bold text-slate-600">Order</span><input type="number" name="fields[{{ $index }}][sort_order]" value="{{ old("fields.$index.sort_order", $field->sort_order) }}" min="0" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                                        <label class="md:col-span-2"><span class="text-xs font-bold text-slate-600">Help text</span><input name="fields[{{ $index }}][help_text]" value="{{ old("fields.$index.help_text", $field->help_text) }}" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                                        <label class="md:col-span-2"><span class="text-xs font-bold text-slate-600">Options, one per line</span><textarea name="fields[{{ $index }}][options]" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm">{{ old("fields.$index.options", implode("\n", $field->options ?? [])) }}</textarea></label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-lg border border-dashed border-slate-300 bg-white p-5">
                        <h2 class="font-bold text-slate-950">Add another field</h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <label><span class="text-xs font-bold text-slate-600">Key</span><input name="new_field[key]" placeholder="example_field" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                            <label><span class="text-xs font-bold text-slate-600">Label</span><input name="new_field[label]" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                            <label><span class="text-xs font-bold text-slate-600">Type</span><select name="new_field[type]" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm">@foreach($fieldTypes as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></label>
                            <label class="flex items-end gap-2 pb-2 text-sm font-bold text-slate-600"><input type="checkbox" name="new_field[is_required]" value="1" class="rounded border-slate-300 text-emerald-700">Required</label>
                            <label class="md:col-span-2"><span class="text-xs font-bold text-slate-600">Placeholder</span><input name="new_field[placeholder]" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="md:col-span-2"><span class="text-xs font-bold text-slate-600">Help text</span><input name="new_field[help_text]" class="mt-1 block h-10 w-full rounded-md border-slate-300 text-sm"></label>
                            <label class="md:col-span-2 xl:col-span-4"><span class="text-xs font-bold text-slate-600">Options, one per line</span><textarea name="new_field[options]" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm"></textarea></label>
                        </div>
                    </section>

                    <div class="sticky bottom-4 flex justify-end"><button class="h-12 rounded-md bg-slate-950 px-7 text-sm font-bold text-white shadow-lg hover:bg-slate-800">Save {{ $selectedForm->location->name }} form</button></div>
                </form>
            @endif
        </div>
    </main>
</div>
<script>
document.querySelectorAll('[data-rich-command], [data-rich-format]').forEach(button => {
    button.addEventListener('mousedown', event => event.preventDefault());
    button.addEventListener('click', () => {
        const editor = document.getElementById(button.dataset.richTarget);

        if (!editor) return;

        editor.focus();
        document.execCommand(
            button.dataset.richCommand ? button.dataset.richCommand : 'formatBlock',
            false,
            button.dataset.richFormat || null
        );
    });
});

document.querySelectorAll('.hub-rich-editor').forEach(editor => {
    editor.addEventListener('focus', () => document.execCommand('defaultParagraphSeparator', false, 'p'), { once: true });
});

document.getElementById('hub-registration-form-editor')?.addEventListener('submit', () => {
    document.querySelectorAll('[data-rich-input]').forEach(input => {
        input.value = document.getElementById(input.dataset.richInput)?.innerHTML.trim() || '';
    });
});
</script>
@endsection
