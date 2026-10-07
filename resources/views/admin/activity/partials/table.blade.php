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
                @php
                    $rowUser = $log->user ?? $log->actor;
                    $userActivityUrl = ($linkUsers ?? false) && $rowUser
                        ? route('admin.activity.users.show', array_filter([
                            'user' => $rowUser,
                            'type' => request('type'),
                            'from' => request('from'),
                            'to' => request('to'),
                        ]))
                        : null;
                @endphp
                <tr
                    @if($userActivityUrl)
                        x-data
                        role="link"
                        tabindex="0"
                        x-on:click="if (!$event.target.closest('a, button, input, select')) window.location.href = @js($userActivityUrl)"
                        x-on:keydown.enter.prevent="window.location.href = @js($userActivityUrl)"
                        x-on:keydown.space.prevent="window.location.href = @js($userActivityUrl)"
                        class="cursor-pointer transition hover:bg-slate-50 focus:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                    @endif
                >
                    <td class="px-5 py-4">
                        <div class="font-semibold text-slate-950">{{ $log->title }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $log->description }}</div>
                        <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ str_replace('_', ' ', $log->type) }}</span>
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-700">
                        @if(($linkUsers ?? false) && $log->actor)
                            <a href="{{ route('admin.activity.users.show', array_filter(['user' => $log->actor, 'type' => request('type'), 'from' => request('from'), 'to' => request('to')])) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline dark:text-white dark:hover:text-white">
                                {{ $log->actor->name }}
                            </a>
                        @else
                            {{ $log->actor?->name ?? 'System' }}
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-700">
                        @if(($linkUsers ?? false) && $log->user)
                            <a href="{{ route('admin.activity.users.show', array_filter(['user' => $log->user, 'type' => request('type'), 'from' => request('from'), 'to' => request('to')])) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline dark:text-white dark:hover:text-white">
                                {{ $log->user->name }}
                            </a>
                            <div class="mt-1 text-xs text-slate-500">{{ $log->user->email }}</div>
                        @else
                            {{ $log->user?->name ?? '-' }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">No activity found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
