@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')

            <div class="mb-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Activity Center</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Notifications</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Track new bookings, cancellations, reschedules, and status changes.</p>
            </div>

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" class="flex gap-2">
                    <select name="type" class="rounded-md border-slate-300 text-sm">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white">Filter</button>
                </form>
                <form method="POST" action="{{ route('admin.notifications.read') }}">
                    @csrf
                    <button class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">Mark all read</button>
                </form>
            </div>

            <section class="notification-list-section overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="divide-y divide-slate-100">
                    @forelse($notifications as $notification)
                        <article class="admin-notification-card flex gap-4 px-5 py-4 {{ $notification->read_at ? 'is-read bg-white' : 'is-unread bg-blue-50/50' }}">
                            <div class="mt-1 h-3 w-3 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-blue-700' }}"></div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-bold text-slate-950">{{ $notification->title }}</h2>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">{{ str_replace('_', ' ', $notification->type) }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ $notification->message }}</p>
                                <p class="mt-2 text-xs font-semibold text-slate-400">
                                    {{ $notification->location?->name ?? 'All branches' }} | {{ $notification->created_at->format('M d, Y H:i') }}
                                </p>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-12 text-center text-sm text-slate-500">No notifications yet.</div>
                    @endforelse
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $notifications->links() }}</div>
            </section>
        </div>
    </main>
</div>
@endsection
