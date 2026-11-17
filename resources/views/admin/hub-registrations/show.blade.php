@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')
    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <a href="{{ route('admin.hub-registrations.index', $location) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Back to {{ $location->name }} registrations</a>
            @if(session('success'))<div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ session('error') }}</div>@endif
            <div class="mt-4 border-b border-slate-200 pb-6">
                <p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Application #{{ $registration->id }}</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $registration->full_name_en }}</h1>
                <p class="mt-2 text-sm text-slate-600">Submitted {{ $registration->submitted_at?->format('M d, Y \a\t H:i') }} for {{ $location->name }} Hub.</p>
                <p class="mt-3 text-sm font-bold {{ $registration->status === 'accepted' ? 'text-emerald-700' : ($registration->status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">Status: {{ $registration->status === 'pending' ? 'Needs review' : ucfirst($registration->status) }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($registration->status !== 'accepted')
                        <form method="POST" action="{{ route('admin.hub-registrations.accept', [$location, $registration]) }}" onsubmit="return confirm('Accept this application and create the student account?')">@csrf<button class="h-10 rounded-md bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Accept application</button></form>
                    @endif
                    @if($registration->status === 'pending')
                        <form method="POST" action="{{ route('admin.hub-registrations.reject', [$location, $registration]) }}" onsubmit="return confirm('Reject this application?')">@csrf<button class="h-10 rounded-md border border-rose-300 px-4 text-sm font-bold text-rose-700 hover:bg-rose-50">Reject application</button></form>
                    @endif
                </div>
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
                                    <a href="{{ route('admin.hub-registrations.files.download', [$location, $file]) }}" target="_blank" rel="noopener" class="font-bold text-blue-700 hover:underline">Open {{ $file->original_name }}</a>
                                    <a href="{{ route('admin.hub-registrations.files.download', [$location, $file, 'download' => 1]) }}" class="ml-3 text-xs font-bold text-slate-600 hover:underline">Download</a>
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
