@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')
    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <a href="{{ route('admin.hub-registrations.index', $location) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Back to {{ $location->name }} registrations</a>
            <div class="mt-4 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Application #{{ $registration->id }}</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $registration->full_name_en }}</h1>
                <p class="mt-2 text-sm text-slate-600">Submitted {{ $registration->submitted_at?->format('M d, Y \a\t H:i') }} for {{ $location->name }} Hub.</p>
            </div>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <dl class="divide-y divide-slate-100">
                    @foreach($form->fields as $field)
                        @php
                            $file = $registration->files->firstWhere('hub_registration_field_id', $field->id);
                        @endphp
                        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[15rem_minmax(0,1fr)]">
                            <dt class="text-sm font-bold text-slate-600">{{ $field->label }}</dt>
                            <dd class="min-w-0 whitespace-pre-wrap break-words text-sm text-slate-900">
                                @if($field->type === 'file' && $file)
                                    <a href="{{ route('admin.hub-registrations.files.download', [$location, $file]) }}" class="font-bold text-blue-700 hover:underline">Download {{ $file->original_name }}</a>
                                    <span class="ml-2 text-xs text-slate-500">{{ number_format($file->size / 1024, 1) }} KB</span>
                                @elseif($field->type === 'file')
                                    <span class="text-slate-400">No file</span>
                                @else
                                    {{ $registration->answers[(string) $field->id] ?? '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    </main>
</div>
@endsection
