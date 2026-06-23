<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigratePayloadDataTest extends TestCase
{
    use RefreshDatabase;

    private function createPayloadSourceTables(): void
    {
        // Create Payload-shaped tables in a separate SQLite connection
        config(['database.connections.payload_source' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $source = DB::connection('payload_source');

        // Payload tickets table (camelCase columns)
        $source->statement('CREATE TABLE tickets (
            id TEXT PRIMARY KEY,
            title_ka TEXT,
            title_en TEXT,
            title_ru TEXT,
            description_ka TEXT,
            description_en TEXT,
            "priceGel" REAL,
            quantity INTEGER,
            "eventDate" TEXT,
            location TEXT,
            status TEXT,
            "saleUrl" TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload sold_tickets table
        $source->statement('CREATE TABLE sold_tickets (
            id TEXT PRIMARY KEY,
            "personalNumber" TEXT,
            email TEXT,
            name TEXT,
            surname TEXT,
            amount REAL,
            status TEXT,
            "originalTicketId" TEXT,
            "eventName" TEXT,
            "eventDate" TEXT,
            location TEXT,
            "paidAt" TEXT,
            "scannedAt" TEXT,
            "scannedBy" TEXT,
            "pgOrderId" INTEGER,
            "pgHppUrl" TEXT,
            "pgPassword" TEXT,
            "qrCode" TEXT,
            "failedAt" TEXT,
            "failReason" TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload products table
        $source->statement('CREATE TABLE products (
            id TEXT PRIMARY KEY,
            title_ka TEXT,
            title_en TEXT,
            description_ka TEXT,
            description_en TEXT,
            "priceGel" REAL,
            category TEXT,
            "isVip" INTEGER DEFAULT 0,
            "imageId" TEXT,
            status TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload pages table
        $source->statement('CREATE TABLE pages (
            id TEXT PRIMARY KEY,
            title_ka TEXT,
            title_en TEXT,
            slug TEXT,
            "isPublished" INTEGER DEFAULT 0,
            "navLabel_ka" TEXT,
            "navLabel_en" TEXT,
            "routePath" TEXT,
            "showInNav" INTEGER DEFAULT 0,
            "navOrder" INTEGER DEFAULT 0,
            "featuredOnHome" INTEGER DEFAULT 0,
            layout TEXT,
            "contentBlocks" TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload site_settings table
        $source->statement('CREATE TABLE site_settings (
            id TEXT PRIMARY KEY,
            key TEXT,
            value TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload music_tracks table
        $source->statement('CREATE TABLE music_tracks (
            id TEXT PRIMARY KEY,
            title_ka TEXT,
            title_en TEXT,
            artist TEXT,
            "audioFileId" TEXT,
            "order" INTEGER,
            status TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload partners table
        $source->statement('CREATE TABLE partners (
            id TEXT PRIMARY KEY,
            name TEXT,
            description_ka TEXT,
            description_en TEXT,
            "logoId" TEXT,
            url TEXT,
            "order" INTEGER,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload posts table
        $source->statement('CREATE TABLE posts (
            id TEXT PRIMARY KEY,
            title_ka TEXT,
            title_en TEXT,
            body_ka TEXT,
            body_en TEXT,
            slug TEXT,
            status TEXT,
            featured INTEGER DEFAULT 0,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');

        // Payload media table
        $source->statement('CREATE TABLE media (
            id TEXT PRIMARY KEY,
            filename TEXT,
            "mimeType" TEXT,
            "filesize" INTEGER,
            alt TEXT,
            url TEXT,
            "createdAt" TEXT,
            "updatedAt" TEXT
        )');
    }

    private function seedPayloadData(): void
    {
        $source = DB::connection('payload_source');

        $source->table('tickets')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'title_ka' => 'კონცერტი',
            'title_en' => 'Concert',
            'title_ru' => null,
            'description_ka' => 'აღწერა',
            'description_en' => 'Description',
            'priceGel' => 50.00,
            'quantity' => 100,
            'eventDate' => '2026-08-01',
            'location' => 'Tbilisi',
            'status' => 'active',
            'saleUrl' => null,
            'createdAt' => '2026-06-01T10:00:00Z',
            'updatedAt' => '2026-06-01T10:00:00Z',
        ]);

        $source->table('pages')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'title_ka' => 'ჩვენს შესახებ',
            'title_en' => 'About Us',
            'slug' => 'about',
            'isPublished' => 1,
            'navLabel_ka' => 'ჩვენ',
            'navLabel_en' => 'About',
            'routePath' => '/about',
            'showInNav' => 1,
            'navOrder' => 1,
            'featuredOnHome' => 0,
            'layout' => 'default',
            'contentBlocks' => '[]',
            'createdAt' => '2026-06-01T10:00:00Z',
            'updatedAt' => '2026-06-01T10:00:00Z',
        ]);

        $source->table('site_settings')->insert([
            'id' => '1',
            'key' => 'heroTitle',
            'value' => '{"ka":"გამარჯობა","en":"Hello"}',
            'createdAt' => '2026-06-01T10:00:00Z',
            'updatedAt' => '2026-06-01T10:00:00Z',
        ]);

        $source->table('media')->insert([
            'id' => '550e8400-e29b-41d4-a716-446655440003',
            'filename' => 'hero.jpg',
            'mimeType' => 'image/jpeg',
            'filesize' => 102400,
            'alt' => 'Hero image',
            'url' => '/media/hero.jpg',
            'createdAt' => '2026-06-01T10:00:00Z',
            'updatedAt' => '2026-06-01T10:00:00Z',
        ]);
    }

    public function test_migrate_command_requires_source_dsn(): void
    {
        $this->artisan('migrate:from-payload')
            ->expectsOutputToContain('--source-dsn is required')
            ->assertExitCode(1);
    }

    public function test_migrate_transfers_tickets(): void
    {
        $this->createPayloadSourceTables();
        $this->seedPayloadData();

        $this->artisan('migrate:from-payload', [
            '--source-dsn' => 'payload_source',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('tickets', [
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'price_gel' => 50.00,
            'quantity' => 100,
            'status' => 'active',
            'location' => 'Tbilisi',
        ]);
    }

    public function test_migrate_transfers_pages(): void
    {
        $this->createPayloadSourceTables();
        $this->seedPayloadData();

        $this->artisan('migrate:from-payload', [
            '--source-dsn' => 'payload_source',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('pages', [
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'slug' => 'about',
            'is_published' => true,
        ]);
    }

    public function test_migrate_transfers_site_settings(): void
    {
        $this->createPayloadSourceTables();
        $this->seedPayloadData();

        $this->artisan('migrate:from-payload', [
            '--source-dsn' => 'payload_source',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'heroTitle',
        ]);
    }

    public function test_migrate_transfers_media(): void
    {
        $this->createPayloadSourceTables();
        $this->seedPayloadData();

        $this->artisan('migrate:from-payload', [
            '--source-dsn' => 'payload_source',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('media', [
            'id' => '550e8400-e29b-41d4-a716-446655440003',
            'filename' => 'hero.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function test_migrate_is_idempotent(): void
    {
        $this->createPayloadSourceTables();
        $this->seedPayloadData();

        $this->artisan('migrate:from-payload', ['--source-dsn' => 'payload_source'])->assertExitCode(0);
        $this->artisan('migrate:from-payload', ['--source-dsn' => 'payload_source'])->assertExitCode(0);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('pages', 1);
    }
}
