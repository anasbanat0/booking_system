<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use App\Models\BookingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSiteContentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $canManageAllBranches = $request->user()->canManageAllBranches();
        $contents = SiteContent::query()
            ->orderBy('key')
            ->get()
            ->keyBy('key');
        $locations = $canManageAllBranches
            ? BookingLocation::orderBy('name')->get()
            : BookingLocation::whereKey($request->user()->booking_location_id)->get();
        $siteLogoUrl = $contents['site_logo_url']->value ?? '';
        $supporterGallery = json_decode($contents['supporter_gallery']->value ?? '[]', true);
        $supporterGallery = is_array($supporterGallery) ? array_values($supporterGallery) : [];
        $heroBackgroundGallery = json_decode($contents['hero_background_gallery']->value ?? '[]', true);
        $heroBackgroundGallery = is_array($heroBackgroundGallery) ? array_values($heroBackgroundGallery) : [];
        $eventGallery = json_decode($contents['event_gallery']->value ?? '[]', true);
        $eventGallery = is_array($eventGallery) ? array_values($eventGallery) : [];

        return view('admin.content.index', compact(
            'canManageAllBranches',
            'contents',
            'locations',
            'siteLogoUrl',
            'supporterGallery',
            'heroBackgroundGallery',
            'eventGallery'
        ));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $validated = $request->validate([
            'content' => ['required', 'array'],
            'content.*' => ['nullable', 'string'],
            'site_logo_file' => ['nullable', 'image', 'max:4096'],
            'remove_site_logo' => ['nullable', 'boolean'],
            'supporter_gallery_files' => ['nullable', 'array'],
            'supporter_gallery_files.*' => ['image', 'max:4096'],
            'hero_background_gallery_files' => ['nullable', 'array'],
            'hero_background_gallery_files.*' => ['image', 'max:8192'],
            'event_gallery_files' => ['nullable', 'array'],
            'event_gallery_files.*' => ['image', 'max:8192'],
            'event_gallery_titles' => ['nullable', 'array'],
            'event_gallery_titles.*' => ['nullable', 'string', 'max:120'],
            'hub_supporter_gallery_files' => ['nullable', 'array'],
            'hub_supporter_gallery_files.*' => ['nullable', 'array'],
            'hub_supporter_gallery_files.*.*' => ['image', 'max:4096'],
            'remove_supporter_gallery' => ['nullable', 'array'],
            'remove_hero_background_gallery' => ['nullable', 'array'],
            'remove_event_gallery' => ['nullable', 'array'],
            'remove_hub_supporter_gallery' => ['nullable', 'array'],
        ]);

        foreach ($validated['content'] as $key => $value) {
            if (!$this->canUpdateKey($request, $key)) {
                continue;
            }

            SiteContent::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        if ($this->canUpdateKey($request, 'site_logo_url')) {
            $this->syncSiteLogo($request);
        }

        if ($this->canUpdateKey($request, 'supporter_gallery')) {
            $this->syncGallery(
                'supporter_gallery',
                $request->file('supporter_gallery_files', []),
                $request->input('remove_supporter_gallery', [])
            );
        }

        if ($this->canUpdateKey($request, 'hero_background_gallery')) {
            $this->syncGallery(
                'hero_background_gallery',
                $request->file('hero_background_gallery_files', []),
                $request->input('remove_hero_background_gallery', []),
                [],
                'hero'
            );
        }

        if ($this->canUpdateKey($request, 'event_gallery')) {
            $this->syncGallery(
                'event_gallery',
                $request->file('event_gallery_files', []),
                $request->input('remove_event_gallery', []),
                $request->input('event_gallery_titles', []),
                'inside-hub'
            );
        }

        foreach ($request->file('hub_supporter_gallery_files', []) as $locationId => $files) {
            $key = 'hub_' . $locationId . '_supporter_gallery';

            if ($this->canUpdateKey($request, $key)) {
                $this->syncGallery(
                    $key,
                    $files,
                    $request->input('remove_hub_supporter_gallery.' . $locationId, [])
                );
            }
        }

        foreach ($request->input('remove_hub_supporter_gallery', []) as $locationId => $removeIndexes) {
            $key = 'hub_' . $locationId . '_supporter_gallery';

            if (!$request->hasFile('hub_supporter_gallery_files.' . $locationId) && $this->canUpdateKey($request, $key)) {
                $this->syncGallery($key, [], $removeIndexes);
            }
        }

        return back()->with('success', 'Homepage content updated successfully.');
    }

    private function syncSiteLogo(Request $request): void
    {
        if ($request->boolean('remove_site_logo')) {
            $this->deleteCurrentLogo();

            SiteContent::updateOrCreate(['key' => 'site_logo_url'], ['value' => '']);
            SiteContent::updateOrCreate(['key' => 'site_logo_path'], ['value' => '']);

            return;
        }

        $file = $request->file('site_logo_file');

        if (!$file) {
            return;
        }

        $this->deleteCurrentLogo();

        $path = $file->store('site', 'public');

        SiteContent::updateOrCreate(['key' => 'site_logo_path'], ['value' => $path]);
        SiteContent::updateOrCreate(['key' => 'site_logo_url'], ['value' => '/storage/' . ltrim($path, '/')]);
    }

    private function deleteCurrentLogo(): void
    {
        $path = SiteContent::getValue('site_logo_path', '');

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function canUpdateKey(Request $request, string $key): bool
    {
        if ($request->user()->canManageAllBranches()) {
            return true;
        }

        return str_starts_with($key, 'hub_' . $request->user()->booking_location_id . '_');
    }

    private function syncGallery(string $key, array $files, array $removeIndexes, array $titleUpdates = [], string $directory = 'supporters'): void
    {
        $gallery = $this->galleryItems($key);
        foreach ($titleUpdates as $index => $title) {
            if (isset($gallery[(int) $index])) {
                $gallery[(int) $index]['name'] = trim((string) $title);
            }
        }

        $removeIndexes = collect($removeIndexes)->map(fn ($index) => (int) $index)->all();

        if ($removeIndexes !== []) {
            foreach ($removeIndexes as $index) {
                if (isset($gallery[$index]['path'])) {
                    Storage::disk('public')->delete($gallery[$index]['path']);
                }

                unset($gallery[$index]);
            }

            $gallery = array_values($gallery);
        }

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store($directory, 'public');

            $gallery[] = [
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'path' => $path,
                'url' => '/storage/' . ltrim($path, '/'),
            ];
        }

        SiteContent::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode(array_values($gallery), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]
        );
    }

    private function galleryItems(string $key): array
    {
        $value = SiteContent::getValue($key, '[]');
        $items = json_decode($value, true);

        return is_array($items) ? array_values($items) : [];
    }
}
