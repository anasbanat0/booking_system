<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_contents')
            ->where('key', 'like', '%supporter_gallery')
            ->orderBy('key')
            ->each(function ($content) {
                $items = json_decode($content->value ?: '[]', true);

                if (!is_array($items)) {
                    return;
                }

                $changed = false;

                foreach ($items as &$item) {
                    if (!isset($item['url']) || !is_string($item['url'])) {
                        continue;
                    }

                    $url = preg_replace('#^https?://[^/]+/storage/#', '/storage/', $item['url']);

                    if ($url !== $item['url']) {
                        $item['url'] = $url;
                        $changed = true;
                    }
                }

                unset($item);

                if (!$changed) {
                    return;
                }

                DB::table('site_contents')
                    ->where('id', $content->id)
                    ->update([
                        'value' => json_encode(array_values($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
