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
                    Update the homepage, Arabic student instructions, branch pages, supporters, contact info, and social links shown to students.
                </p>
                @unless($canManageAllBranches)
                    <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">
                        Staff can edit only their assigned hub page. Global homepage fields are visible for context and are controlled by the main admin.
                    </p>
                @endunless
            </div>

            <form id="content-editor-form" method="POST" action="{{ route('admin.content.update') }}" enctype="multipart/form-data" class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-base font-extrabold text-slate-950">Homepage hero</h2>
                    <p class="mt-1 text-sm text-slate-500">Controls the first screen: browser title, brand text, headline, intro, and main buttons.</p>

                    @if($canManageAllBranches)
                        <div class="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                            <h3 class="text-sm font-extrabold text-slate-950">Site logo</h3>
                            <p class="mt-1 text-sm text-slate-500">This logo appears across the homepage, footer, dashboards, and error pages.</p>

                            <div class="mt-3 grid gap-4 lg:grid-cols-[13rem_1fr] lg:items-center">
                                <div class="flex h-20 w-full items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                    <img src="{{ $siteLogoUrl ?: Vite::asset('resources/images/logo.png') }}" alt="Current site logo" class="block object-contain" style="max-height: 3.5rem; max-width: 100%; width: auto;">
                                </div>

                                <div class="min-w-0 flex-1">
                                    <label class="block">
                                        <span class="text-sm font-semibold text-slate-700">Upload new logo</span>
                                        <input type="file" name="site_logo_file" accept="image/*"
                                               class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm shadow-sm file:me-4 file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white focus:border-blue-500 focus:ring-blue-500">
                                    </label>

                                    @if($siteLogoUrl)
                                        <label class="mt-3 flex items-center gap-2 text-sm font-bold text-rose-700">
                                            <input type="checkbox" name="remove_site_logo" value="1" class="rounded border-slate-300 text-rose-600">
                                            Remove uploaded logo and use default
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

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
                            'hub_buttons_heading' => 'Hub buttons heading',
                            'hub_buttons_description' => 'Hub buttons description',
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
                        <span class="text-sm font-semibold text-slate-700">Student instructions - Arabic</span>
                        <span class="mt-1 block text-xs text-slate-500">This content controls the student instructions page. Use H2 for main colored headings and H3 for smaller section titles.</span>
                        <div class="mb-4 mt-3 grid gap-4 lg:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Instructions hero eyebrow</span>
                                <input name="content[instructions_hero_eyebrow]" dir="rtl" value="{{ old('content.instructions_hero_eyebrow', $contents['instructions_hero_eyebrow']->value ?? 'Samir Foundation Medical Hub') }}"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Instructions hero title</span>
                                <input name="content[instructions_hero_title]" dir="rtl" value="{{ old('content.instructions_hero_title', $contents['instructions_hero_title']->value ?? 'تعليمات الحجز واستخدام المكان') }}"
                                       class="mt-1 block w-full rounded-md border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <label class="block lg:col-span-2">
                                <span class="text-sm font-semibold text-slate-700">Instructions hero description</span>
                                <textarea name="content[instructions_hero_description]" rows="3" dir="rtl"
                                          class="mt-1 block w-full rounded-md border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.instructions_hero_description', $contents['instructions_hero_description']->value ?? 'مساحة هادئة ومجهزة للطلبة الذين يحتاجون إلى كهرباء مستقرة، اتصال إنترنت، وبيئة مناسبة للدراسة أو تقديم الامتحانات الإلكترونية.') }}</textarea>
                            </label>
                        </div>
                        <input type="hidden" name="content[instructions_ar]" value="{{ old('content.instructions_ar', $contents['instructions_ar']->value ?? '') }}" data-editor-input="instructions-ar">
                        <div class="mt-2 flex flex-wrap gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 p-2">
                            <button type="button" data-editor-format="p" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">P</button>
                            <button type="button" data-editor-format="h2" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold text-teal-700">H2</button>
                            <button type="button" data-editor-format="h3" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold text-blue-700">H3</button>
                            <button type="button" data-editor-command="bold" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">B</button>
                            <button type="button" data-editor-command="italic" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold italic">I</button>
                            <button type="button" data-editor-command="insertUnorderedList" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">Bullets</button>
                            <button type="button" data-editor-command="insertOrderedList" data-editor-target="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold">Numbers</button>
                            <button type="button" data-html-toggle="instructions-ar" class="rounded border border-slate-300 px-2 py-1 text-xs font-bold text-blue-700">HTML</button>
                        </div>
                        <div id="instructions-ar" contenteditable="true" dir="rtl" class="min-h-96 rounded-b-md border border-slate-300 bg-white p-3 text-right text-sm leading-7 focus:outline-none focus:ring-2 focus:ring-blue-500">{!! old('content.instructions_ar', $contents['instructions_ar']->value ?? '<h2>أيام عمل المكان</h2><p>من السبت إلى الخميس.</p>') !!}</div>
                        <textarea id="instructions-ar-html" data-html-editor="instructions-ar" dir="rtl" class="hidden min-h-96 w-full rounded-b-md border border-slate-300 bg-slate-950 p-3 text-right font-mono text-sm leading-6 text-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Supporters</span>
                    <input name="content[supporters]" value="{{ old('content.supporters', $contents['supporters']->value ?? '') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="mt-1 block text-xs text-slate-500">Separate names with commas.</span>
                </label>

                @if($canManageAllBranches)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-base font-extrabold text-slate-950">General supporters gallery</h2>
                        <p class="mt-1 text-sm text-slate-500">Upload logos/images from your computer. These appear on the main homepage.</p>

                        @if(!empty($supporterGallery ?? []))
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach(($supporterGallery ?? []) as $index => $item)
                                    <label class="rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="flex h-24 items-center justify-center rounded-md bg-slate-50 p-2">
                                            <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['name'] ?? 'Supporter' }}" class="max-h-20 w-auto object-contain">
                                        </div>
                                        <div class="mt-3 flex items-center gap-2 text-xs font-bold text-rose-700">
                                            <input type="checkbox" name="remove_supporter_gallery[]" value="{{ $index }}" class="rounded border-slate-300 text-rose-600">
                                            Remove
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <label class="mt-4 block">
                            <span class="text-sm font-semibold text-slate-700">Add supporter images</span>
                            <input type="file" name="supporter_gallery_files[]" accept="image/*" multiple
                                   class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm shadow-sm file:me-4 file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white focus:border-blue-500 focus:ring-blue-500">
                        </label>
                    </div>
                @endif

                @if($locations->isNotEmpty())
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-base font-extrabold text-slate-950">Hub pages</h2>
                        <p class="mt-1 text-sm text-slate-500">Each hub can have its own homepage content and supporters. Staff can save only their assigned hub section.</p>

                        @php
                            $hubFields = [
                                'page_title' => 'Browser page title',
                                'brand_title' => 'Brand title',
                                'brand_subtitle' => 'Brand subtitle',
                                'hero_eyebrow' => 'Hero eyebrow',
                                'hero_title' => 'Hero title',
                                'primary_cta_guest' => 'Guest CTA button',
                                'primary_cta_auth' => 'Authenticated CTA button',
                                'partners_heading' => 'Partners heading',
                            ];
                        @endphp

                        <div class="mt-4 space-y-5">
                            @foreach($locations as $location)
                                @php
                                    $prefix = 'hub_' . $location->id . '_';
                                    $hubGallery = json_decode($contents[$prefix . 'supporter_gallery']->value ?? '[]', true);
                                    $hubGallery = is_array($hubGallery) ? array_values($hubGallery) : [];
                                @endphp
                                <section class="rounded-lg border border-slate-200 bg-white p-4">
                                    <h3 class="text-lg font-extrabold text-slate-950">{{ $location->name }} Hub</h3>
                                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                        @foreach($hubFields as $key => $label)
                                            <label class="block">
                                                <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                                                <input name="content[{{ $prefix . $key }}]" value="{{ old('content.' . $prefix . $key, $contents[$prefix . $key]->value ?? '') }}"
                                                       class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </label>
                                        @endforeach
                                    </div>

                                    <label class="mt-4 block">
                                        <span class="text-sm font-semibold text-slate-700">Project intro</span>
                                        <textarea name="content[{{ $prefix }}project_intro]" rows="3"
                                                  class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content.' . $prefix . 'project_intro', $contents[$prefix . 'project_intro']->value ?? '') }}</textarea>
                                    </label>

                                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <h4 class="text-sm font-extrabold text-slate-950">{{ $location->name }} supporters gallery</h4>
                                        @if($hubGallery)
                                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                                @foreach($hubGallery as $index => $item)
                                                    <label class="rounded-lg border border-slate-200 bg-white p-3">
                                                        <div class="flex h-24 items-center justify-center rounded-md bg-slate-50 p-2">
                                                            <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['name'] ?? 'Supporter' }}" class="max-h-20 w-auto object-contain">
                                                        </div>
                                                        <div class="mt-3 flex items-center gap-2 text-xs font-bold text-rose-700">
                                                            <input type="checkbox" name="remove_hub_supporter_gallery[{{ $location->id }}][]" value="{{ $index }}" class="rounded border-slate-300 text-rose-600">
                                                            Remove
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                        <label class="mt-4 block">
                                            <span class="text-sm font-semibold text-slate-700">Add {{ $location->name }} supporter images</span>
                                            <input type="file" name="hub_supporter_gallery_files[{{ $location->id }}][]" accept="image/*" multiple
                                                   class="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm shadow-sm file:me-4 file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white focus:border-blue-500 focus:ring-blue-500">
                                        </label>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                @endif

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
