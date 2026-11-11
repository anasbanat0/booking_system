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
                <a href="{{ route('admin.hub-registrations.export', ['location' => $location] + request()->only('search', 'from', 'to')) }}" class="inline-flex h-11 items-center justify-center rounded-md bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">Export Excel</a>
            </div>

            <form method="GET" class="mt-6 grid gap-3 border-y border-slate-200 bg-white px-4 py-4 sm:grid-cols-2 xl:grid-cols-[minmax(18rem,1fr)_12rem_12rem_auto_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search name or email" class="h-11 rounded-md border-slate-300 text-sm">
                <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" class="h-11 rounded-md border-slate-300 text-sm">
                <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" class="h-11 rounded-md border-slate-300 text-sm">
                <button class="h-11 rounded-md bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                <a href="{{ route('admin.hub-registrations.index', $location) }}" class="inline-flex h-11 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700 hover:bg-slate-100">Clear</a>
            </form>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="font-bold text-slate-950">Applications</h2>
                    <span class="text-sm font-semibold text-slate-500">{{ number_format($registrations->total()) }} results</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50"><tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Applicant</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Submitted</th>
                            <th class="px-5 py-3 text-right text-xs font-bold uppercase text-slate-500">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($registrations as $registration)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $registration->full_name_en }}</p><p dir="rtl" class="mt-1 text-left text-sm text-slate-500">{{ $registration->full_name_ar }}</p></td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $registration->email }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $registration->submitted_at?->format('M d, Y H:i') }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('admin.hub-registrations.show', [$location, $registration]) }}" class="inline-flex h-9 items-center rounded-md border border-slate-300 px-3 text-sm font-bold text-slate-700 hover:bg-slate-100">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-slate-500">No registrations match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($registrations->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $registrations->links() }}</div>@endif
            </section>
        </div>
    </main>
</div>
@endsection
