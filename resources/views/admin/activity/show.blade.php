@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 lg:flex">
    @include('admin.partials.sidebar')

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('admin.partials.topbar')

            <div class="mb-8">
                <a href="{{ route('admin.activity.index', request()->only(['type', 'from', 'to'])) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Back to activity log</a>
                <h1 class="mt-3 text-3xl font-bold text-slate-950">{{ $selectedUser->name }}</h1>
                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-600">
                    <span>{{ $selectedUser->email }}</span>
                    @if($selectedUser->phone)<span dir="ltr">{{ $selectedUser->phone }}</span>@endif
                    <span>{{ ucfirst($selectedUser->role) }}</span>
                    <span>{{ $selectedUser->managedLocation?->name ?? 'No branch' }}</span>
                </div>
            </div>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <form method="GET" class="grid items-end gap-3 md:grid-cols-2 lg:grid-cols-[minmax(200px,1fr)_minmax(170px,1fr)_minmax(170px,1fr)_120px_auto]">
                        <label class="grid gap-1 text-xs font-bold uppercase text-slate-500">
                            Type
                            <select name="type" class="h-12 rounded-md border-slate-300 text-sm font-normal normal-case text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All activity types</option>
                                @foreach($types as $type)
                                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-xs font-bold uppercase text-slate-500">
                            From
                            <input type="date" name="from" value="{{ request('from') }}" lang="en" dir="ltr" x-on:click="$el.showPicker?.()" class="h-12 cursor-pointer rounded-md border-slate-300 text-sm font-normal text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="grid gap-1 text-xs font-bold uppercase text-slate-500">
                            To
                            <input type="date" name="to" value="{{ request('to') }}" lang="en" dir="ltr" x-on:click="$el.showPicker?.()" class="h-12 cursor-pointer rounded-md border-slate-300 text-sm font-normal text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <button class="h-12 rounded-md bg-slate-950 px-4 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                        @if(request()->hasAny(['type', 'from', 'to']))
                            <a href="{{ route('admin.activity.users.show', $selectedUser) }}" class="inline-flex h-12 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Clear</a>
                        @endif
                    </form>
                    <x-input-error :messages="$errors->all()" class="mt-3" />
                </div>

                @include('admin.activity.partials.table', ['linkUsers' => true])

                <div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>
            </section>
        </div>
    </main>
</div>
@endsection
