@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')

            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">System</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Activity Log</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Track login, booking, import, and administrative changes.</p>
            </div>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,260px)_220px_auto]">
                        <input name="search" value="{{ request('search') }}" placeholder="Search activity" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <select name="type" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Activity</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Actor</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">Student</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase text-slate-500">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-950">{{ $log->title }}</div>
                                        <div class="mt-1 text-sm text-slate-500">{{ $log->description }}</div>
                                        <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ str_replace('_', ' ', $log->type) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $log->actor?->name ?? 'System' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $log->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">No activity yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>
            </section>
        </div>
    </main>
</div>
@endsection
