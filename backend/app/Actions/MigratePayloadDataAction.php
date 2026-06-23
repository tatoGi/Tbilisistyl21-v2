<?php

namespace App\Actions;

use App\Models\SiteSetting;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;

class MigratePayloadDataAction
{
    public function execute(string $connectionName, ?OutputStyle $output = null): void
    {
        $source = DB::connection($connectionName);

        $this->migrateMedia($source, $output);
        $this->migrateTickets($source, $output);
        $this->migrateSoldTickets($source, $output);
        $this->migrateProducts($source, $output);
        $this->migratePages($source, $output);
        $this->migratePosts($source, $output);
        $this->migrateMusicTracks($source, $output);
        $this->migratePartners($source, $output);
        $this->migrateSiteSettings($source, $output);
    }

    private function migrateMedia($source, ?OutputStyle $output): void
    {
        $rows = $source->table('media')->get();
        $output?->writeln("Migrating {$rows->count()} media records...");

        foreach ($rows as $row) {
            $this->upsert('media', $row->id, [
                'filename' => $row->filename,
                'path' => $row->url ?? "media/{$row->filename}",
                'mime_type' => $row->mimeType ?? $row->mime_type ?? null,
                'size' => $row->filesize ?? $row->size ?? null,
                'alt' => $row->alt,
            ]);
        }
    }

    private function migrateTickets($source, ?OutputStyle $output): void
    {
        $rows = $source->table('tickets')->get();
        $output?->writeln("Migrating {$rows->count()} tickets...");

        foreach ($rows as $row) {
            $this->upsert('tickets', $row->id, [
                'title' => json_encode($this->buildTranslatable($row, 'title')),
                'description' => json_encode($this->buildTranslatable($row, 'description')),
                'price_gel' => $row->priceGel ?? $row->price_gel ?? 0,
                'quantity' => $row->quantity ?? 0,
                'event_date' => $row->eventDate ?? $row->event_date ?? null,
                'location' => $row->location,
                'status' => $row->status ?? 'draft',
                'sale_url' => $row->saleUrl ?? $row->sale_url ?? null,
            ]);
        }
    }

    private function migrateSoldTickets($source, ?OutputStyle $output): void
    {
        if (!$this->tableExists($source, 'sold_tickets')) {
            $output?->writeln('No sold_tickets table found, skipping.');
            return;
        }

        $rows = $source->table('sold_tickets')->get();
        $output?->writeln("Migrating {$rows->count()} sold tickets...");

        foreach ($rows as $row) {
            $this->upsert('sold_tickets', $row->id, [
                'personal_number' => $row->personalNumber ?? $row->personal_number ?? '',
                'email' => $row->email,
                'name' => $row->name,
                'surname' => $row->surname,
                'amount' => $row->amount ?? 0,
                'status' => $row->status ?? 'pending',
                'original_ticket_id' => $row->originalTicketId ?? $row->original_ticket_id ?? null,
                'event_name' => $row->eventName ?? $row->event_name ?? null,
                'event_date' => $row->eventDate ?? $row->event_date ?? null,
                'location' => $row->location,
                'paid_at' => $row->paidAt ?? $row->paid_at ?? null,
                'scanned_at' => $row->scannedAt ?? $row->scanned_at ?? null,
                'scanned_by' => $row->scannedBy ?? $row->scanned_by ?? null,
                'pg_order_id' => $row->pgOrderId ?? $row->pg_order_id ?? null,
                'pg_hpp_url' => $row->pgHppUrl ?? $row->pg_hpp_url ?? null,
                'pg_password' => $row->pgPassword ?? $row->pg_password ?? null,
                'qr_code' => $row->qrCode ?? $row->qr_code ?? null,
                'failed_at' => $row->failedAt ?? $row->failed_at ?? null,
                'fail_reason' => $row->failReason ?? $row->fail_reason ?? null,
            ]);
        }
    }

    private function migrateProducts($source, ?OutputStyle $output): void
    {
        if (!$this->tableExists($source, 'products')) {
            $output?->writeln('No products table found, skipping.');
            return;
        }

        $rows = $source->table('products')->get();
        $output?->writeln("Migrating {$rows->count()} products...");

        foreach ($rows as $row) {
            $this->upsert('products', $row->id, [
                'title' => json_encode($this->buildTranslatable($row, 'title')),
                'description' => json_encode($this->buildTranslatable($row, 'description')),
                'price_gel' => $row->priceGel ?? $row->price_gel ?? 0,
                'category' => $row->category,
                'is_vip' => (bool) ($row->isVip ?? $row->is_vip ?? false),
                'image_id' => $row->imageId ?? $row->image_id ?? null,
                'status' => $row->status ?? 'draft',
            ]);
        }
    }

    private function migratePages($source, ?OutputStyle $output): void
    {
        $rows = $source->table('pages')->get();
        $output?->writeln("Migrating {$rows->count()} pages...");

        foreach ($rows as $row) {
            $this->upsert('pages', $row->id, [
                'title' => json_encode($this->buildTranslatable($row, 'title')),
                'nav_label' => json_encode($this->buildTranslatable($row, 'navLabel', 'nav_label')),
                'slug' => $row->slug,
                'route_path' => $row->routePath ?? $row->route_path ?? null,
                'show_in_nav' => (bool) ($row->showInNav ?? $row->show_in_nav ?? false),
                'nav_order' => $row->navOrder ?? $row->nav_order ?? 0,
                'featured_on_home' => (bool) ($row->featuredOnHome ?? $row->featured_on_home ?? false),
                'layout' => $row->layout ?? 'default',
                'content_blocks' => json_encode($this->jsonDecode($row->contentBlocks ?? $row->content_blocks ?? null) ?? []),
                'is_published' => (bool) ($row->isPublished ?? $row->is_published ?? false),
            ]);
        }
    }

    private function migratePosts($source, ?OutputStyle $output): void
    {
        if (!$this->tableExists($source, 'posts')) {
            $output?->writeln('No posts table found, skipping.');
            return;
        }

        $rows = $source->table('posts')->get();
        $output?->writeln("Migrating {$rows->count()} posts...");

        foreach ($rows as $row) {
            $this->upsert('posts', $row->id, [
                'title' => json_encode($this->buildTranslatable($row, 'title')),
                'body' => json_encode($this->buildTranslatable($row, 'body')),
                'slug' => $row->slug,
                'status' => $row->status ?? 'draft',
                'featured' => (bool) ($row->featured ?? false),
            ]);
        }
    }

    private function migrateMusicTracks($source, ?OutputStyle $output): void
    {
        if (!$this->tableExists($source, 'music_tracks')) {
            $output?->writeln('No music_tracks table found, skipping.');
            return;
        }

        $rows = $source->table('music_tracks')->get();
        $output?->writeln("Migrating {$rows->count()} music tracks...");

        foreach ($rows as $row) {
            $this->upsert('music_tracks', $row->id, [
                'title' => json_encode($this->buildTranslatable($row, 'title')),
                'artist' => $row->artist,
                'audio_file_id' => $row->audioFileId ?? $row->audio_file_id ?? null,
                'order' => $row->order ?? 0,
                'status' => $row->status ?? 'active',
            ]);
        }
    }

    private function migratePartners($source, ?OutputStyle $output): void
    {
        if (!$this->tableExists($source, 'partners')) {
            $output?->writeln('No partners table found, skipping.');
            return;
        }

        $rows = $source->table('partners')->get();
        $output?->writeln("Migrating {$rows->count()} partners...");

        foreach ($rows as $row) {
            $this->upsert('partners', $row->id, [
                'name' => $row->name,
                'description' => json_encode($this->buildTranslatable($row, 'description')),
                'logo_id' => $row->logoId ?? $row->logo_id ?? null,
                'url' => $row->url,
                'order' => $row->order ?? 0,
            ]);
        }
    }

    private function migrateSiteSettings($source, ?OutputStyle $output): void
    {
        $rows = $source->table('site_settings')->get();
        $output?->writeln("Migrating {$rows->count()} site settings...");

        foreach ($rows as $row) {
            $value = $this->jsonDecode($row->value);
            SiteSetting::set($row->key, $value);
        }
    }

    /**
     * Upsert a row by ID, bypassing HasUuids auto-generation.
     */
    private function upsert(string $table, string $id, array $data): void
    {
        $now = now();
        $exists = DB::table($table)->where('id', $id)->exists();

        if ($exists) {
            DB::table($table)->where('id', $id)->update(array_merge($data, [
                'updated_at' => $now,
            ]));
        } else {
            DB::table($table)->insert(array_merge($data, [
                'id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function buildTranslatable(object $row, string $camelField, ?string $snakeField = null): array
    {
        $result = [];
        $snake = $snakeField ?? $camelField;
        $locales = ['ka', 'en', 'ru', 'ua'];

        foreach ($locales as $locale) {
            $camelKey = "{$camelField}_{$locale}";
            $snakeKey = "{$snake}_{$locale}";

            $value = $row->$camelKey ?? $row->$snakeKey ?? null;
            if ($value !== null && $value !== '') {
                $result[$locale] = $value;
            }
        }

        return $result;
    }

    private function jsonDecode(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? $decoded : $value;
        }
        return $value;
    }

    private function tableExists($connection, string $table): bool
    {
        try {
            $connection->table($table)->limit(1)->get();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
