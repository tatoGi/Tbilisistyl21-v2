<?php

namespace Tests\Feature;

use App\Models\Dj;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_scope_excludes_drafts(): void
    {
        Dj::create(['name' => 'Live One', 'order' => 1, 'status' => 'published']);
        Dj::create(['name' => 'Hidden One', 'order' => 2, 'status' => 'draft']);

        $names = Dj::published()->pluck('name')->all();

        $this->assertSame(['Live One'], $names);
    }

    public function test_bio_is_translatable(): void
    {
        $dj = Dj::create([
            'name' => 'Translator',
            'bio' => ['ka' => 'ქართული', 'en' => 'English'],
            'status' => 'published',
        ]);

        $this->assertSame('ქართული', $dj->getTranslation('bio', 'ka'));
        $this->assertSame('English', $dj->getTranslation('bio', 'en'));
    }
}
