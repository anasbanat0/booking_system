@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')
    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-emerald-700">Hub applications</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $location->name }} Registrations</h1>
                    <p class="mt-2 text-sm text-slate-600">Review submitted applications and export the current result set.</p>
                </div>
                <a href="{{ route('admin.hub-registrations.export', ['location' => $location] + request()->only('search', 'from', 'to', 'status')) }}" class="inline-flex h-11 items-center justify-center rounded-md bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">Export Excel</a>
            </div>

            @if(session('success'))<div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">Select at least one application before accepting selected applications.</div>@endif

            <form method="GET" class="mt-6 grid gap-3 border-y border-slate-200 bg-white px-4 py-4 sm:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_11rem_11rem_11rem_auto_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search name or email" class="h-11 rounded-md border-slate-300 text-sm">
                <select name="status" aria-label="Application status" class="h-11 rounded-md border-slate-300 text-sm">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Needs review</option>
                    <option value="accepted" @selected(request('status') === 'accepted')>Accepted</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" class="h-11 rounded-md border-slate-300 text-sm">
                <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" class="h-11 rounded-md border-slate-300 text-sm">
                <button class="h-11 rounded-md bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                <a href="{{ route('admin.hub-registrations.index', $location) }}" class="inline-flex h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700 hover:bg-slate-100">Clear</a>
            </form>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="font-bold text-slate-950">Applications</h2>
                        <form id="accept-selected-form" method="POST" action="{{ route('admin.hub-registrations.accept-selected', $location) }}" onsubmit="return confirm('Accept the selected applications and create their student accounts?')">
                            @csrf
                            <button class="inline-flex h-9 items-center rounded-md bg-emerald-700 px-4 text-sm font-bold text-white hover:bg-emerald-800">Accept selected</button>
                        </form>
                    </div>
                    <span class="text-sm font-semibold text-slate-500">{{ number_format($registrations->total()) }} results</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <th class="w-12 px-5 py-3"><input id="select-all-registrations" type="checkbox" class="rounded border-slate-300 text-emerald-700" aria-label="Select all applications on this page"></th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Applicant</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Submitted</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase text-slate-500">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($registrations as $registration)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">@if($registration->status !== 'accepted')<input form="accept-selected-form" name="registration_ids[]" value="{{ $registration->id }}" type="checkbox" class="registration-checkbox rounded border-slate-300 text-emerald-700" aria-label="Select {{ $registration->full_name_en }}">@endif</td>
                                    <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $registration->full_name_en }}</p><p dir="rtl" class="mt-1 text-left text-sm text-slate-500">{{ $registration->full_name_ar }}</p></td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $registration->email }}</td>
                                    <td class="px-5 py-4">
                                        @php
                                            $statusClasses = match($registration->status) {
                                                'accepted' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                                                'rejected' => 'bg-rose-50 text-rose-800 ring-rose-200',
                                                default => 'bg-amber-50 text-amber-800 ring-amber-200',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusClasses }}">{{ $registration->status === 'pending' ? 'Needs review' : ucfirst($registration->status) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $registration->submitted_at?->format('M d, Y H:i') }}</td>
                                    <td class="px-5 py-4"><div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.hub-registrations.show', [$location, $registration]) }}" class="inline-flex h-9 items-center rounded-md border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-100">View</a>
                                        @if($registration->status !== 'accepted')
                                            <form method="POST" action="{{ route('admin.hub-registrations.accept', [$location, $registration]) }}" onsubmit="return confirm('Accept this application and create the student account?')">@csrf<button class="inline-flex h-9 items-center rounded-md bg-emerald-700 px-3 text-sm font-bold text-white hover:bg-emerald-800">Accept</button></form>
                                        @endif
                                        @if($registration->status !== 'accepted' && $registration->status !== 'rejected')
                                            <form method="POST" action="{{ route('admin.hub-registrations.reject', [$location, $registration]) }}" onsubmit="return confirm('Reject this application?')">@csrf<button class="inline-flex h-9 items-center rounded-md border border-rose-300 px-3 text-sm font-bold text-rose-700 hover:bg-rose-50">Reject</button></form>
                                        @endif
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">No registrations match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($registrations->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $registrations->links() }}</div>@endif
            </section>
        </div>
    </main>
</div>
<script>
document.getElementById('select-all-registrations')?.addEventListener('change', event => {
    document.querySelectorAll('.registration-checkbox').forEach(checkbox => checkbox.checked = event.target.checked);
});
</script>
@endsection
