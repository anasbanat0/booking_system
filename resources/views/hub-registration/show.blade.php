<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form->title }} - Samir Foundation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3" aria-label="Back to homepage">
                @php
                    $logoUrl = \App\Models\SiteContent::getValue('site_logo_url', '');
                @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Samir Foundation" class="h-11 w-auto max-w-64 object-contain sm:h-14 sm:max-w-80">
                @else
                    <div class="flex h-11 w-11 items-center justify-center rounded-md bg-emerald-700 text-sm font-black text-white">SF</div>
                @endif
            </a>
            <a href="{{ url('/') }}" class="inline-flex h-10 shrink-0 items-center rounded-md border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50 sm:px-4"><span class="sm:hidden">Home</span><span class="hidden sm:inline">Back to homepage</span></a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        @if(session('registration_success'))
            <section class="mx-auto max-w-2xl border-t-4 border-emerald-600 bg-white px-6 py-12 text-center shadow-sm sm:px-10">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl font-black text-emerald-700">&#10003;</div>
                <p class="mt-6 text-xs font-extrabold uppercase tracking-widest text-emerald-700">Application saved</p>
                <h1 class="mt-2 text-3xl font-black">{{ session('registration_success.title') }}</h1>
                <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-700">{{ session('registration_success.message') }}</p>
                <a href="{{ url('/') }}" class="mt-8 inline-flex h-11 items-center justify-center rounded-md bg-slate-950 px-6 text-sm font-extrabold text-white hover:bg-slate-800">Return to homepage</a>
            </section>
        @else
            <div class="grid min-w-0 gap-7 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_25rem]">
                <section class="min-w-0 overflow-hidden border border-slate-200 bg-white shadow-sm">
                    <div class="border-t-4 border-emerald-600 px-5 py-7 sm:px-8">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-emerald-700">{{ $form->eyebrow }}</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal sm:text-4xl">{{ $form->title }}</h1>
                        @if($form->description)
                            <p class="mt-4 max-w-4xl leading-7 text-slate-800">{{ $form->description }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('hub-registration.store', $location) }}" enctype="multipart/form-data" class="border-t border-slate-200 px-5 py-7 sm:px-8" novalidate>
                        @csrf
                        <div class="grid min-w-0 gap-x-5 gap-y-6 sm:grid-cols-2">
                            @foreach($fields as $field)
                                @php
                                    $name = $field->type === 'file' ? 'files['.$field->id.']' : 'answers['.$field->id.']';
                                    $oldValue = old('answers.'.$field->id);
                                    $wide = in_array($field->type, ['textarea', 'radio', 'file'], true);
                                @endphp
                                <fieldset class="min-w-0 {{ $wide ? 'sm:col-span-2' : '' }}">
                                    <label for="field-{{ $field->id }}" class="block text-sm font-extrabold text-slate-800">
                                        {{ $field->label }}
                                        @if($field->is_required)<span class="text-rose-600" aria-hidden="true">*</span>@endif
                                    </label>

                                    @if($field->type === 'textarea')
                                        <textarea id="field-{{ $field->id }}" name="{{ $name }}" rows="4" placeholder="{{ $field->placeholder }}" @required($field->is_required)
                                                  class="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ $oldValue }}</textarea>
                                    @elseif($field->type === 'radio')
                                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                            @foreach($field->options ?? [] as $option)
                                                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold hover:border-emerald-500 hover:bg-emerald-50/50">
                                                    <input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($oldValue === $option) @required($field->is_required) class="border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($field->type === 'select')
                                        <select id="field-{{ $field->id }}" name="{{ $name }}" @required($field->is_required) class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                            <option value="">Select an option</option>
                                            @foreach($field->options ?? [] as $option)
                                                <option value="{{ $option }}" @selected($oldValue === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($field->type === 'file')
                                        <input id="field-{{ $field->id }}" type="file" name="{{ $name }}" accept=".jpg,.jpeg,.png,.pdf" @required($field->is_required)
                                               class="mt-2 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-bold file:text-slate-900 hover:file:bg-slate-200">
                                    @else
                                        <input id="field-{{ $field->id }}" type="{{ $field->type }}" name="{{ $name }}" value="{{ $oldValue }}" placeholder="{{ $field->placeholder }}" @required($field->is_required)
                                               class="mt-2 block h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    @endif

                                    @if($field->help_text)<p class="mt-1.5 text-xs font-medium leading-5 text-slate-600">{{ $field->help_text }}</p>@endif
                                    @error(($field->type === 'file' ? 'files.' : 'answers.').$field->id)
                                        <p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </fieldset>
                            @endforeach
                        </div>

                        <div class="mt-8 border-t border-slate-200 pt-6">
                            <button class="inline-flex h-12 w-full items-center justify-center rounded-md bg-emerald-700 px-6 text-sm font-extrabold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:w-auto">
                                {{ $form->submit_button }}
                            </button>
                            <p class="mt-3 text-xs font-medium text-slate-600">Your information is stored securely and used only to review your Medical Hub application.</p>
                        </div>
                    </form>
                </section>

                <aside class="border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6 xl:p-7">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-emerald-700">Before you submit</p>
                    <div class="mt-4 whitespace-pre-line text-base font-medium leading-8 text-slate-800">{{ $form->instructions }}</div>
                    <div class="mt-5 border-t border-slate-200 pt-5">
                        <p class="text-sm font-extrabold text-slate-900">{{ $location->name }} Hub</p>
                        <p class="mt-1 text-sm font-medium leading-5 text-slate-700">Fields marked with * are required.</p>
                    </div>
                </aside>
            </div>
        @endif
    </main>
</body>
</html>
