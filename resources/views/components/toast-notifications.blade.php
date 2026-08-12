@php
    $statusMessages = [
        'profile-updated' => 'Profile updated successfully.',
        'password-updated' => 'Password updated successfully.',
        'verification-link-sent' => 'Verification link sent.',
    ];

    $toasts = collect();

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type)) {
            $toasts->push([
                'type' => $type,
                'title' => [
                    'success' => 'All set',
                    'error' => 'Action needed',
                    'warning' => 'Heads up',
                    'info' => 'Notice',
                ][$type],
                'message' => session($type),
            ]);
        }
    }

    if (session()->has('status')) {
        $status = session('status');

        $toasts->push([
            'type' => 'success',
            'title' => 'Done',
            'message' => $statusMessages[$status] ?? $status,
        ]);
    }

    if ($errors->any()) {
        $toasts->push([
            'type' => 'error',
            'title' => 'Please check this',
            'message' => $errors->first(),
        ]);
    }

    $styles = [
        'success' => [
            'shell' => 'border-emerald-200 bg-white shadow-emerald-950/15',
            'glow' => 'bg-emerald-500/12',
            'bar' => 'from-emerald-400 to-teal-500',
            'icon' => 'bg-emerald-600 text-white ring-emerald-100',
            'halo' => 'bg-emerald-50',
            'title' => 'text-emerald-950',
        ],
        'error' => [
            'shell' => 'border-rose-200 bg-white shadow-rose-950/15',
            'glow' => 'bg-rose-500/12',
            'bar' => 'from-rose-500 to-pink-500',
            'icon' => 'bg-rose-600 text-white ring-rose-100',
            'halo' => 'bg-rose-50',
            'title' => 'text-rose-950',
        ],
        'warning' => [
            'shell' => 'border-amber-200 bg-white shadow-amber-950/15',
            'glow' => 'bg-amber-500/14',
            'bar' => 'from-amber-400 to-orange-500',
            'icon' => 'bg-amber-500 text-white ring-amber-100',
            'halo' => 'bg-amber-50',
            'title' => 'text-amber-950',
        ],
        'info' => [
            'shell' => 'border-sky-200 bg-white shadow-sky-950/15',
            'glow' => 'bg-sky-500/12',
            'bar' => 'from-sky-400 to-blue-500',
            'icon' => 'bg-sky-600 text-white ring-sky-100',
            'halo' => 'bg-sky-50',
            'title' => 'text-sky-950',
        ],
    ];
@endphp

@if($toasts->isNotEmpty())
    <div
        aria-live="polite"
        class="fixed left-1/2 top-1/2 z-[100] flex w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 flex-col gap-3"
    >
        @foreach($toasts as $toast)
            @php($toastStyles = $styles[$toast['type']] ?? $styles['info'])

            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5200)"
                x-show="show"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                class="relative overflow-hidden rounded-2xl border {{ $toastStyles['shell'] }} shadow-2xl backdrop-blur"
                role="alert"
            >
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r {{ $toastStyles['bar'] }}"></div>
                <div class="pointer-events-none absolute -left-16 -top-16 h-36 w-36 rounded-full {{ $toastStyles['glow'] }} blur-2xl"></div>

                <div class="relative flex gap-4 px-5 py-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $toastStyles['halo'] }} ring-1 ring-black/5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $toastStyles['icon'] }} ring-4">
                        @if($toast['type'] === 'success')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 12.75 3.5 3.5 7-8.5" />
                            </svg>
                        @elseif($toast['type'] === 'error')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" d="M8 8l8 8M16 8l-8 8" />
                            </svg>
                        @elseif($toast['type'] === 'warning')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5v5.25" />
                                <path stroke-linecap="round" d="M12 16.5h.01" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                <path stroke-linecap="round" d="M12 11v5" />
                                <path stroke-linecap="round" d="M12 8h.01" />
                            </svg>
                        @endif
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-base font-extrabold {{ $toastStyles['title'] }}">{{ $toast['title'] }}</p>
                        <p class="mt-1 text-[15px] leading-7 text-slate-600">{{ $toast['message'] }}</p>
                    </div>

                    <button
                        type="button"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        x-on:click="show = false"
                        aria-label="Dismiss notification"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
