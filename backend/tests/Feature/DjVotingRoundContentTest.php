<?php

namespace Tests\Feature;

use App\Models\Dj;
use App\Models\DjVotingRound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DjVotingRoundContentTest extends TestCase
{
    use RefreshDatabase;

    private function openRoundWithDj(array $attributes = []): DjVotingRound
    {
        $round = DjVotingRound::create(array_merge([
            'title' => 'Now',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ], $attributes));

        $round->djs()->attach(Dj::create(['name' => 'A', 'status' => 'published'])->id);

        return $round;
    }

    public function test_heading_and_subtitle_are_translatable(): void
    {
        $round = $this->openRoundWithDj([
            'heading' => ['ka' => 'ვინ დაუკრავს?', 'en' => 'Who will play?'],
            'subtitle' => ['ka' => 'აირჩიე დიჯეი', 'en' => 'Pick a DJ'],
        ]);

        $this->assertSame('ვინ დაუკრავს?', $round->getTranslation('heading', 'ka'));
        $this->assertSame('Who will play?', $round->getTranslation('heading', 'en'));
        $this->assertSame('Pick a DJ', $round->getTranslation('subtitle', 'en'));
    }

    /**
     * The frontend localizes with its own t(), so the API must hand back every
     * locale rather than a single collapsed string.
     */
    public function test_api_returns_every_locale_for_the_round_copy(): void
    {
        $this->openRoundWithDj([
            'heading' => ['ka' => 'ვინ დაუკრავს?', 'en' => 'Who will play?'],
            'subtitle' => ['ka' => 'აირჩიე დიჯეი', 'en' => 'Pick a DJ'],
        ]);

        $response = $this->getJson('/api/dj-vote', ['X-Vote-Token' => 'tok-1'])->assertOk();

        $this->assertSame(
            ['ka' => 'ვინ დაუკრავს?', 'en' => 'Who will play?'],
            $response->json('round.heading'),
        );
        $this->assertSame(
            ['ka' => 'აირჩიე დიჯეი', 'en' => 'Pick a DJ'],
            $response->json('round.subtitle'),
        );
    }

    /**
     * Copy is optional: an admin who leaves it blank gets the frontend's own
     * translated defaults, so the section must not break or emit empty strings.
     */
    public function test_unset_copy_is_returned_as_null(): void
    {
        $this->openRoundWithDj();

        $response = $this->getJson('/api/dj-vote', ['X-Vote-Token' => 'tok-1'])->assertOk();

        $this->assertNull($response->json('round.heading'));
        $this->assertNull($response->json('round.subtitle'));
    }

    /** A round filled in for only one language must not invent the others. */
    public function test_partially_filled_copy_keeps_only_the_filled_locales(): void
    {
        $this->openRoundWithDj(['heading' => ['ka' => 'მხოლოდ ქართული']]);

        $response = $this->getJson('/api/dj-vote', ['X-Vote-Token' => 'tok-1'])->assertOk();

        $this->assertSame(['ka' => 'მხოლოდ ქართული'], $response->json('round.heading'));
    }
}
