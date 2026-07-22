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
                    Update the homepage, English student instructions, supporters, contact info, and social links shown to students.
                </p>
            </div>

            <form id="content-editor-form" method="POST" action="{{ route('admin.content.update') }}" class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Homepage hero</h2>
                    <p class="mt-1 text-sm text-slate-500">Controls the first screen: browser title, brand text, headline, intro, and main buttons.</p>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Browser page title</span>
                            <input name="content[page_title]" value="{{ old('content.page_title', $contents['page_title']->value ?? 'Medical Hub - Samir Foundation') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Hero eyebrow</span>
                            <input name="content[hero_eyebrow]" value="{{ old('content.hero_eyebrow', $contents['hero_eyebrow']->value ?? 'Quiet power for serious study') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Brand title</span>
                            <input name="content[brand_title]" value="{{ old('content.brand_title', $contents['brand_title']->value ?? 'Samir Foundation') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Brand subtitle</span>
                            <input name="content[brand_subtitle]" value="{{ old('content.brand_subtitle', $contents['brand_subtitle']->value ?? 'Medical Hub') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Hero title</span>
                        <textarea name="content[hero_title]" rows="2"
                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.hero_title', $contents['hero_title']->value ?? 'Reserve a calm, connected seat for exams and study.') }}</textarea>
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Project intro</span>
                    <textarea name="content[project_intro]" rows="4"
                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.project_intro', $contents['project_intro']->value ?? '') }}</textarea>
                </label>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Buttons and stats labels</h2>
                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        @foreach([
                            'primary_cta_guest' => 'Guest CTA button',
                            'primary_cta_auth' => 'Authenticated CTA button',
                            'stat_students_label' => 'Students stat label',
                            'stat_bookings_label' => 'Bookings stat label',
                            'stat_seats_label' => 'Seats stat label',
                            'stat_branches_label' => 'Branches stat label',
                        ] as $key => $label)
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                                <input name="content[{{ $key }}]" value="{{ old('content.' . $key, $contents[$key]->value ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Access cards</h2>
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        @foreach([
                            'student_card_eyebrow' => 'Student card eyebrow',
                            'student_card_title' => 'Student card title',
                            'student_card_guest_button' => 'Student login button',
                            'student_card_help_button' => 'Student help button',
                            'student_card_auth_button' => 'Student authenticated button',
                            'team_card_eyebrow' => 'Team card eyebrow',
                            'team_staff_button' => 'Staff button',
                            'team_admin_button' => 'Admin button',
                            'partners_heading' => 'Partners heading',
                            'partners_empty_text' => 'Partners empty text',
                        ] as $key => $label)
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                                <input name="content[{{ $key }}]" value="{{ old('content.' . $key, $contents[$key]->value ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                        @endforeach
                    </div>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Student card description</span>
                        <textarea name="content[student_card_description]" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.student_card_description', $contents['student_card_description']->value ?? '') }}</textarea>
                    </label>
                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Team card description</span>
                        <textarea name="content[team_card_description]" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.team_card_description', $contents['team_card_description']->value ?? '') }}</textarea>
                    </label>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Homepage steps</h2>
                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        @foreach([1, 2, 3] as $step)
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <label class="block">
                                    <span class="text-sm font-semibold text-slate-700">Step {{ $step }} label</span>
                                    <input name="content[step_{{ $step }}_label]" value="{{ old('content.step_' . $step . '_label', $contents['step_' . $step . '_label']->value ?? str_pad((string) $step, 2, '0', STR_PAD_LEFT)) }}"
                                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </label>
                                <label class="mt-3 block">
                                    <span class="text-sm font-semibold text-slate-700">Step {{ $step }} title</span>
                                    <input name="content[step_{{ $step }}_title]" value="{{ old('content.step_' . $step . '_title', $contents['step_' . $step . '_title']->value ?? '') }}"
                                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </label>
                                <label class="mt-3 block">
                                    <span class="text-sm font-semibold text-slate-700">Step {{ $step }} description</span>
                                    <textarea name="content[step_{{ $step }}_description]" rows="4"
                                              class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.step_' . $step . '_description', $contents['step_' . $step . '_description']->value ?? '') }}</textarea>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Student instructions - English</span>
                        <span class="mt-1 block text-xs text-slate-500">This content controls the student Booking Instructions page. You can edit, add, or remove sections here.</span>
                        <input type="hidden" name="content[instructions_en]" value="{{ old('content.instructions_en', $contents['instructions_en']->value ?? '') }}" data-editor-input="instructions-en">
                        <div class="mt-2 flex flex-wrap gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2">
                            <button type="button" data-editor-format="p" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">P</button>
                            <button type="button" data-editor-format="h2" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">H2</button>
                            <button type="button" data-editor-format="h3" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">H3</button>
                            <button type="button" data-editor-command="bold" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">B</button>
                            <button type="button" data-editor-command="italic" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold italic">I</button>
                            <button type="button" data-editor-command="insertUnorderedList" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">Bullets</button>
                            <button type="button" data-editor-command="insertOrderedList" data-editor-target="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">Numbers</button>
                            <button type="button" data-html-toggle="instructions-en" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold text-blue-700">HTML</button>
                        </div>
                        <div id="instructions-en" contenteditable="true" class="min-h-96 rounded-b-md border border-slate-300 bg-white p-3 text-sm leading-6 focus:outline-none focus:ring-2 focus:ring-blue-500">{!! old('content.instructions_en', $contents['instructions_en']->value ?? '<p>Read the booking rules carefully before choosing a weekly slot.</p>') !!}</div>
                        <textarea id="instructions-en-html" data-html-editor="instructions-en" class="hidden min-h-96 w-full rounded-b-md border border-slate-300 bg-slate-950 p-3 font-mono text-sm leading-6 text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </label>

                    @if(false)
                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Student instructions - Arabic</span>
                        <input type="hidden" name="content[instructions_ar]" value="{{ old('content.instructions_ar', $contents['instructions_ar']->value ?? '') }}" data-editor-input="instructions-ar">
                        <div class="mt-2 flex gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2">
                            <button type="button" data-editor-command="bold" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">B</button>
                            <button type="button" data-editor-command="insertUnorderedList" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">List</button>
                        </div>
                        <div id="instructions-ar" contenteditable="true" dir="rtl" class="min-h-48 rounded-b-md border border-slate-300 bg-white p-3 text-right text-sm leading-6 focus:outline-none focus:ring-2 focus:ring-blue-500">{!! old('content.instructions_ar', $contents['instructions_ar']->value ?? '<p>اقرأ تعليمات الحجز قبل اختيار الموعد.</p>') !!}</div>
                    </label>
                    @endif
                </div>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Supporters</span>
                    <input name="content[supporters]" value="{{ old('content.supporters', $contents['supporters']->value ?? '') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="mt-1 block text-xs text-slate-500">Separate names with commas.</span>
                </label>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Footer content</h2>
                    <p class="mt-1 text-sm text-slate-500">These fields control the public footer, supporting partner note, and optional call-to-action.</p>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Footer title</span>
                            <input name="content[footer_title]" value="{{ old('content.footer_title', $contents['footer_title']->value ?? 'Samir Foundation Medical Hub') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Call-to-action text</span>
                            <input name="content[footer_cta_text]" value="{{ old('content.footer_cta_text', $contents['footer_cta_text']->value ?? 'Need to update information or add a supporting partner announcement?') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Call-to-action button</span>
                            <input name="content[footer_cta_button]" value="{{ old('content.footer_cta_button', $contents['footer_cta_button']->value ?? 'Open link') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Contact heading</span>
                            <input name="content[footer_contact_heading]" value="{{ old('content.footer_contact_heading', $contents['footer_contact_heading']->value ?? 'Contact') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Support heading</span>
                            <input name="content[footer_support_heading]" value="{{ old('content.footer_support_heading', $contents['footer_support_heading']->value ?? 'Support and links') }}"
                                   class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Footer description</span>
                        <textarea name="content[footer_description]" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.footer_description', $contents['footer_description']->value ?? 'A student-focused hub prepared for calm study, online exams, stable electricity, and reliable internet access.') }}</textarea>
                    </label>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Supporters note</span>
                        <textarea name="content[supporters_note]" rows="3"
                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.supporters_note', $contents['supporters_note']->value ?? 'Supporting education access through prepared study spaces in Gaza and Khan Younis.') }}</textarea>
                    </label>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Call-to-action URL</span>
                        <input name="content[footer_cta_url]" value="{{ old('content.footer_cta_url', $contents['footer_cta_url']->value ?? '') }}"
                               placeholder="https://example.org"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="mt-1 block text-xs text-slate-500">Optional. Leave empty to hide the footer button.</span>
                    </label>

                    <label class="mt-4 block">
                        <span class="text-sm font-semibold text-slate-700">Footer bottom text</span>
                        <input name="content[footer_bottom_text]" value="{{ old('content.footer_bottom_text', $contents['footer_bottom_text']->value ?? 'Samir Foundation Medical Hub. Built for focused learning access.') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                </div>

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
function syncHtmlEditor(targetId) {
    const editor = document.getElementById(targetId);
    const htmlEditor = document.querySelector(`[data-html-editor="${targetId}"]`);

    if (editor && htmlEditor && !htmlEditor.classList.contains('hidden')) {
        editor.innerHTML = htmlEditor.value;
    }
}

document.querySelectorAll('[data-editor-command]').forEach(button => {
    button.addEventListener('click', () => {
        const editor = document.getElementById(button.dataset.editorTarget);
        syncHtmlEditor(button.dataset.editorTarget);
        editor.focus();
        document.execCommand(button.dataset.editorCommand, false, null);
    });
});

document.querySelectorAll('[data-editor-format]').forEach(button => {
    button.addEventListener('click', () => {
        const editor = document.getElementById(button.dataset.editorTarget);
        syncHtmlEditor(button.dataset.editorTarget);
        editor.focus();
        document.execCommand('formatBlock', false, button.dataset.editorFormat);
    });
});

document.querySelectorAll('[data-html-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const targetId = button.dataset.htmlToggle;
        const editor = document.getElementById(targetId);
        const htmlEditor = document.querySelector(`[data-html-editor="${targetId}"]`);

        if (!editor || !htmlEditor) {
            return;
        }

        if (htmlEditor.classList.contains('hidden')) {
            htmlEditor.value = editor.innerHTML.trim();
            htmlEditor.classList.remove('hidden');
            editor.classList.add('hidden');
            button.classList.add('bg-blue-50');
        } else {
            editor.innerHTML = htmlEditor.value;
            htmlEditor.classList.add('hidden');
            editor.classList.remove('hidden');
            button.classList.remove('bg-blue-50');
        }
    });
});

document.getElementById('content-editor-form')?.addEventListener('submit', () => {
    document.querySelectorAll('[data-editor-input]').forEach(input => {
        const editor = document.getElementById(input.dataset.editorInput);
        const htmlEditor = document.querySelector(`[data-html-editor="${input.dataset.editorInput}"]`);
        input.value = htmlEditor && !htmlEditor.classList.contains('hidden')
            ? htmlEditor.value
            : (editor?.innerHTML || '');
    });
});
</script>
@endsection
