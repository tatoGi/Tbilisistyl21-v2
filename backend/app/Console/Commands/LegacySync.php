<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\MusicTrack;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\SiteSetting;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Syncs the LOCAL legacy snapshot (see `legacy:snapshot`) into the Laravel
 * models. Idempotent (updateOrCreate) so it can be re-run safely; never writes
 * to production Neon. Use --dry-run to preview and --only to limit scope.
 */
class LegacySync extends Command
{
    protected $signature = 'legacy:sync {--dry-run : Report what would change without writing} {--only= : Comma list: media,pages,tickets,products,music,partners,posts,site}';

    protected $description = 'Sync the local legacy snapshot into the Laravel models (idempotent)';

    private const LOCALES = ['ka', 'en', 'ru', 'ua'];

    private bool $dry = false;

    /** legacy media id => row (filename, alt_*). */
    private array $mediaMap = [];

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');

        try {
            DB::connection('legacy')->statement('SET SESSION default_transaction_read_only = on');
            DB::connection('legacy')->table('media')->limit(1)->get();
        } catch (Throwable $e) {
            $this->error('No legacy snapshot found. Run `php artisan legacy:snapshot` first.');
            $this->line($e->getMessage());
            return self::FAILURE;
        }

        $this->loadMediaMap();

        $only = $this->option('only')
            ? array_map('trim', explode(',', (string) $this->option('only')))
            : ['media', 'pages', 'tickets', 'products', 'music', 'partners', 'posts', 'site'];

        if ($this->dry) {
            $this->warn('DRY RUN -- no changes will be written.');
        }

        foreach ($only as $entity) {
            $method = 'sync' . ucfirst($entity);
            if (!method_exists($this, $method)) {
                $this->warn("Unknown entity: {$entity}");
                continue;
            }
            try {
                $this->{$method}();
            } catch (Throwable $e) {
                $this->error(ucfirst($entity) . ' sync failed: ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info($this->dry ? 'Dry run complete.' : 'Sync complete.');
        return self::SUCCESS;
    }

    private function loadMediaMap(): void
    {
        foreach (DB::connection('legacy')->table('media')->get() as $row) {
            $this->mediaMap[$row->id] = $row;
        }
    }

    // ----------------------------------------------------------------- media

    private function syncMedia(): void
    {
        $rows = DB::connection('legacy')->table('media')->get();
        $this->line("Media: {$rows->count()} legacy records");

        $filenames = [];
        foreach ($rows as $r) {
            $filenames[] = $r->filename;
            if (!$this->dry) {
                $this->upsertById(Media::class, $r->id, [
                    'filename' => $r->filename,
                    'path' => 'media/' . $r->filename,
                    'mime_type' => $r->mime_type,
                    'size' => (int) $r->filesize,
                    'alt' => $r->alt_ka ?? $r->alt_en ?? null,
                ]);
            }
        }

        if (!$this->dry) {
            $this->downloadBlobFiles($filenames);
        }
    }

    private function downloadBlobFiles(array $filenames): void
    {
        $token = config('database.connections.legacy.blob_token');
        if (!$token) {
            $this->warn('  BLOB_READ_WRITE_TOKEN not set -> media records synced, files NOT downloaded.');
            return;
        }

        $this->line('  Listing Vercel Blob store...');
        $urls = [];
        $cursor = null;
        do {
            $resp = Http::withToken($token)->get('https://blob.vercel-storage.com', array_filter([
                'limit' => 1000,
                'cursor' => $cursor,
            ]));
            if (!$resp->ok()) {
                $this->error('  Blob list failed (HTTP ' . $resp->status() . '). Files not downloaded.');
                return;
            }
            $json = $resp->json();
            foreach ($json['blobs'] ?? [] as $b) {
                $urls[$b['pathname']] = $b['url'];
                $urls[basename($b['pathname'])] = $b['url'];
            }
            $cursor = $json['cursor'] ?? null;
        } while (!empty($json['hasMore']));

        $downloaded = 0;
        $missing = 0;
        foreach (array_unique($filenames) as $name) {
            if (Storage::disk('public')->exists('media/' . $name)) {
                continue;
            }
            $url = $urls[$name] ?? $urls[basename($name)] ?? null;
            if (!$url) {
                $missing++;
                continue;
            }
            $file = Http::get($url);
            if ($file->ok()) {
                Storage::disk('public')->put('media/' . $name, $file->body());
                $downloaded++;
            } else {
                $missing++;
            }
        }
        $this->line("  Blob files: {$downloaded} downloaded, {$missing} missing/skipped.");
    }

    // ----------------------------------------------------------------- pages

    private function syncPages(): void
    {
        $pages = DB::connection('legacy')->table('pages')->get();
        $this->line("Pages: {$pages->count()} legacy records");

        foreach ($pages as $p) {
            $blocks = $this->buildPageBlocks($p->id);
            $navLabel = $this->transExplicit($p, 'navlabel');
            if (!array_filter($navLabel)) {
                $navLabel = $this->transExplicit($p, 'title');
            }

            $slug = $this->normalizeSlug($p->slug);
            $this->line("  - {$p->slug}" . ($slug !== $p->slug ? " -> {$slug}" : '') . ': ' . count($blocks) . ' blocks');
            if ($this->dry) {
                continue;
            }

            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $this->transExplicit($p, 'title'),
                    'nav_label' => $navLabel,
                    'show_in_nav' => (bool) $p->show_in_nav,
                    'nav_order' => (int) $p->nav_order,
                    'featured_on_home' => (bool) $p->featured_on_home,
                    'is_published' => ($p->_status ?? 'published') === 'published',
                    'content_blocks' => \App\Filament\Resources\PageResource::normalizeBlockPaths($blocks),
                ]
            );
        }
    }

    private function buildPageBlocks(string $pageId): array
    {
        $items = [];

        foreach (DB::connection('legacy')->table('pages_blocks_rich_text')->where('_parent_id', $pageId)->get() as $r) {
            $items[] = ['order' => $r->_order, 'kind' => 'richText', 'row' => $r];
        }
        foreach (DB::connection('legacy')->table('pages_blocks_image')->where('_parent_id', $pageId)->get() as $r) {
            $items[] = ['order' => $r->_order, 'kind' => 'image', 'row' => $r];
        }
        foreach (DB::connection('legacy')->table('pages_blocks_gallery')->where('_parent_id', $pageId)->get() as $r) {
            $items[] = ['order' => $r->_order, 'kind' => 'gallery', 'row' => $r];
        }

        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);

        $blocks = [];
        foreach ($items as $item) {
            $row = $item['row'];
            if ($item['kind'] === 'richText') {
                $content = [];
                foreach (self::LOCALES as $loc) {
                    $content[$loc] = $this->lexicalToText($row->{"content_{$loc}"} ?? null);
                }
                if (array_filter($content)) {
                    $blocks[] = ['type' => 'richText', 'data' => ['content' => $content]];
                }
            } elseif ($item['kind'] === 'image') {
                $url = $this->mediaUrl($row->image_id);
                if ($url) {
                    $blocks[] = ['type' => 'image', 'data' => ['image' => $url, 'width' => $row->width ?? 'full']];
                }
            } elseif ($item['kind'] === 'gallery') {
                $images = [];
                foreach (DB::connection('legacy')->table('pages_blocks_gallery_images')->where('_parent_id', $row->id)->orderBy('_order')->get() as $gi) {
                    $url = $this->mediaUrl($gi->image_id);
                    if ($url) {
                        $images[] = ['image' => $url];
                    }
                }
                if ($images) {
                    $blocks[] = ['type' => 'gallery', 'data' => ['images' => $images]];
                }
            }
        }

        return $blocks;
    }

    // --------------------------------------------------------------- tickets

    private function syncTickets(): void
    {
        $rows = DB::connection('legacy')->table('tickets')->get();
        $this->line("Tickets: {$rows->count()} legacy records");

        foreach ($rows as $r) {
            if ($this->dry) {
                continue;
            }
            $this->upsertById(Ticket::class, $r->id, [
                'title' => $this->transBase($r, 'title'),
                'description' => $this->transBase($r, 'description'),
                'price_gel' => (float) $r->price_gel,
                'quantity' => (int) $r->quantity,
                'event_date' => $this->safeDate($r->event_date ?? null),
                'location' => $r->location ?: 'Tbilisi',
                'status' => $r->status ?? 'active',
                'sale_url' => $r->sale_url ?: null,
            ]);
        }
    }

    // -------------------------------------------------------------- products

    private function syncProducts(): void
    {
        $rows = DB::connection('legacy')->table('products')->get();
        $this->line("Products: {$rows->count()} legacy records");

        foreach ($rows as $r) {
            $sizes = DB::connection('legacy')->table('products_sizes')
                ->where('_parent_id', $r->id)->orderBy('_order')->get();
            $this->line("  - " . ($r->title ?? $r->id) . ': ' . $sizes->count() . ' sizes');

            if ($this->dry) {
                continue;
            }

            $this->upsertById(Product::class, $r->id, [
                'title' => $this->transBase($r, 'title'),
                'description' => $this->transBase($r, 'description'),
                'price_gel' => (float) $r->price_gel,
                'category' => $r->category ?: null,
                'is_vip' => (bool) $r->is_vip,
                'image_id' => $this->mediaExists($r->image_id) ? $r->image_id : null,
                'status' => $r->status ?? 'active',
            ]);

            foreach ($sizes as $s) {
                ProductSize::updateOrCreate(
                    ['product_id' => $r->id, 'size' => $s->size],
                    ['quantity' => (int) $s->quantity],
                );
            }
        }
    }

    // ----------------------------------------------------------------- music

    private function syncMusic(): void
    {
        $rows = DB::connection('legacy')->table('music_tracks')->get();
        $this->line("Music tracks: {$rows->count()} legacy records");

        foreach ($rows as $r) {
            if ($this->dry) {
                continue;
            }
            $this->upsertById(MusicTrack::class, $r->id, [
                'title' => $this->transBase($r, 'title'),
                'artist' => $r->artist ?? null,
                'audio_file_id' => $this->mediaExists($r->audio_file_id) ? $r->audio_file_id : null,
                'order' => (int) $r->order,
                'status' => $r->status ?? 'active',
            ]);
        }

        if (!$this->dry) {
            Cache::forget(MusicTrack::API_CACHE_KEY);
        }
    }

    // -------------------------------------------------------------- partners

    private function syncPartners(): void
    {
        $rows = DB::connection('legacy')->table('partners')->get();
        $this->line("Partners: {$rows->count()} legacy records");

        foreach ($rows as $r) {
            if ($this->dry) {
                continue;
            }
            $this->upsertById(Partner::class, $r->id, [
                'name' => $r->name,
                'description' => $this->transBase($r, 'description'),
                'logo_id' => $this->mediaExists($r->logo_id) ? $r->logo_id : null,
                'url' => $r->website ?? null,
                'order' => (int) $r->order,
            ]);
        }
    }

    // ----------------------------------------------------------------- posts

    private function syncPosts(): void
    {
        $rows = DB::connection('legacy')->table('posts')->get();
        $this->line("Posts: {$rows->count()} legacy records");

        foreach ($rows as $r) {
            $body = [];
            foreach (DB::connection('legacy')->table('posts_blocks_rich_text')->where('_parent_id', $r->id)->orderBy('_order')->get() as $b) {
                foreach (self::LOCALES as $loc) {
                    $text = $this->lexicalToText($b->{"content_{$loc}"} ?? null);
                    if ($text !== '') {
                        $body[$loc] = trim(($body[$loc] ?? '') . "\n\n" . $text);
                    }
                }
            }

            if ($this->dry) {
                continue;
            }
            Post::updateOrCreate(
                ['slug' => $r->slug],
                [
                    'title' => $this->transExplicit($r, 'title'),
                    'body' => $body,
                    'status' => ($r->_status ?? 'published') === 'published' ? 'published' : 'draft',
                    'featured' => (bool) ($r->featured_on_home ?? false),
                ]
            );
        }
    }

    // ------------------------------------------------------------------ site

    private function syncSite(): void
    {
        $s = DB::connection('legacy')->table('site')->first();
        if (!$s) {
            $this->line('Site settings: none');
            return;
        }
        $this->line('Site settings: 1 legacy record');
        if ($this->dry) {
            return;
        }

        // Non-destructive: only overwrite a setting when the legacy row actually
        // has content, so we never blank our seeded defaults with empty values.
        $heading = array_filter($this->transExplicit($s, 'herotitle'));
        $subheading = array_filter($this->transExplicit($s, 'herotagline'));
        if ($heading || $subheading) {
            $hero = SiteSetting::get('hero', ['heading' => [], 'subheading' => []]);
            if ($heading) {
                $hero['heading'] = $this->transExplicit($s, 'herotitle');
            }
            if ($subheading) {
                $hero['subheading'] = $this->transExplicit($s, 'herotagline');
            }
            SiteSetting::set('hero', $hero);
        }
        if (!empty($s->instagram_url)) {
            SiteSetting::set('instagramUrl', $s->instagram_url);
        }
        if (!empty($s->tiktok_url)) {
            SiteSetting::set('tiktokUrl', $s->tiktok_url);
        }
        if (!empty($s->contact_phone) || !empty($s->contact_email) || !empty($s->contact_address)) {
            $contact = SiteSetting::get('contact', []);
            SiteSetting::set('contact', array_merge(is_array($contact) ? $contact : [], array_filter([
                'phone' => $s->contact_phone ?? null,
                'phoneHref' => !empty($s->contact_phone) ? preg_replace('/[^+0-9]/', '', $s->contact_phone) : null,
                'email' => $s->contact_email ?? null,
                'address' => $s->contact_address ?? null,
            ])));
        }
    }

    // --------------------------------------------------------------- helpers

    /**
     * Upsert preserving the legacy UUID as the primary key (so relations like
     * logo_id / audio_file_id / image_id keep pointing at the right rows).
     */
    private function upsertById(string $modelClass, string $id, array $attributes): void
    {
        $model = $modelClass::find($id) ?? new $modelClass;
        if (!$model->exists) {
            $model->id = $id;
        }
        $model->fill($attributes)->save();
    }

    /** Per-locale columns like title_ka, title_en, title_ru, title_ua. */
    private function transExplicit(object $row, string $base): array
    {
        $out = [];
        foreach (self::LOCALES as $loc) {
            $out[$loc] = $row->{"{$base}_{$loc}"} ?? '';
        }
        return $out;
    }

    /** Base column = ka, plus base_en / base_ru / base_ua. */
    private function transBase(object $row, string $base): array
    {
        return [
            'ka' => $row->{$base} ?? '',
            'en' => $row->{"{$base}_en"} ?? '',
            'ru' => $row->{"{$base}_ru"} ?? '',
            'ua' => $row->{"{$base}_ua"} ?? '',
        ];
    }

    /** Map legacy page slugs onto the slugs our frontend routes already use. */
    private function normalizeSlug(string $slug): string
    {
        return ['line-up' => 'lineup'][$slug] ?? $slug;
    }

    private function mediaUrl(?string $mediaId): ?string
    {
        if (!$mediaId || !isset($this->mediaMap[$mediaId])) {
            return null;
        }

        return \App\Filament\Resources\PageResource::publicUrl('media/' . $this->mediaMap[$mediaId]->filename);
    }

    private function mediaExists(?string $mediaId): bool
    {
        return $mediaId && isset($this->mediaMap[$mediaId]);
    }

    private function safeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function lexicalToText($json): string
    {
        if (!$json) {
            return '';
        }
        $data = is_string($json) ? json_decode($json, true) : $json;
        $root = is_array($data) ? ($data['root'] ?? null) : null;
        if (!is_array($root)) {
            return '';
        }
        $paragraphs = [];
        foreach ($root['children'] ?? [] as $child) {
            $text = trim($this->collectText($child));
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }
        return implode("\n\n", $paragraphs);
    }

    private function collectText($node): string
    {
        if (!is_array($node)) {
            return '';
        }
        if (($node['type'] ?? '') === 'linebreak') {
            return "\n";
        }
        $text = $node['text'] ?? '';
        foreach ($node['children'] ?? [] as $child) {
            $text .= $this->collectText($child);
        }
        return $text;
    }
}
