<?php

namespace Tests\Feature;

use App\Filament\Resources\DjResource;
use App\Filament\Resources\PartnerResource;
use App\Models\Dj;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_sits_in_the_dj_voting_group(): void
    {
        $this->assertSame('DJ Voting', DjResource::getNavigationGroup());
        $this->assertSame(Dj::class, DjResource::getModel());
    }

    public function test_removing_the_photo_clears_the_relation(): void
    {
        $data = DjResource::mergeUploadedMedia(
            ['name' => 'A', 'photo_upload' => null],
            'photo_upload',
            'photo_id',
        );

        $this->assertNull($data['photo_id']);
        $this->assertArrayNotHasKey('photo_upload', $data);
    }

    public function test_untouched_photo_field_is_left_alone(): void
    {
        $data = DjResource::mergeUploadedMedia(['name' => 'A'], 'photo_upload', 'photo_id');

        $this->assertArrayNotHasKey('photo_id', $data);
    }

    public function test_partner_logo_mapping_still_works_through_the_shared_helper(): void
    {
        $data = PartnerResource::mergeUploadedLogo(['name' => 'P', 'logo_upload' => null]);

        $this->assertNull($data['logo_id']);
        $this->assertArrayNotHasKey('logo_upload', $data);
    }
}
