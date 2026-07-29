<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DjVote;
use App\Models\DjVotingRound;
use App\Services\DjVoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DjVoteController extends Controller
{
    public function __construct(private DjVoteService $votes) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->state(DjVotingRound::current(), $this->tokenOrNull($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $token = $this->token($request);
        $round = DjVotingRound::current();

        if (!$round) {
            return response()->json(
                ['message' => 'Voting is not open.'],
                409,
            );
        }

        $djId = $request->input('djId');

        // Only DJs actually on this round's ballot are acceptable.
        if (!$round->djs()->where('djs.id', $djId)->exists()) {
            throw ValidationException::withMessages(['djId' => 'This DJ is not part of the current round.']);
        }

        $this->votes->castVote($round, $djId, $token, $request->ip());

        return response()->json($this->state($round, $token));
    }

    /**
     * The full client-facing payload for a round (or the no-round case).
     *
     * A public read never requires a voter token — only casting a vote does.
     * A null token means "unknown voter": skip the vote lookup and report
     * the not-voted payload rather than guessing an identity.
     */
    private function state(?DjVotingRound $round, ?string $token): array
    {
        if (!$round) {
            return ['round' => null, 'djs' => [], 'hasVoted' => false, 'votedDjId' => null, 'results' => null];
        }

        $vote = $token !== null
            ? DjVote::where('round_id', $round->id)->where('voter_token', $token)->first()
            : null;

        $djs = $round->djs()->published()->with('photo')->get()->map(fn ($dj) => [
            'id' => $dj->id,
            'name' => $dj->name,
            // getTranslations, not `$dj->bio` — the accessor collapses to a
            // single locale, but the frontend localizes with its own `t()`.
            'bio' => $dj->getTranslations('bio') ?: null,
            'photo' => $dj->photo?->filename,
        ])->all();

        return [
            'round' => [
                'id' => $round->id,
                'endsAt' => $round->ends_at->toIso8601String(),
                // Every locale, not a collapsed string — the frontend picks.
                // Null when the admin left it blank, so the frontend can fall
                // back to its own translated defaults.
                'heading' => $round->getTranslations('heading') ?: null,
                'subtitle' => $round->getTranslations('subtitle') ?: null,
            ],
            'djs' => $djs,
            'hasVoted' => (bool) $vote,
            'votedDjId' => $vote?->dj_id,
            // Withheld until this voter has voted.
            'results' => $vote ? $this->votes->results($round) : null,
        ];
    }

    /** Strict: casting a vote requires a real voter token. */
    private function token(Request $request): string
    {
        $token = $this->tokenOrNull($request);

        if ($token === null) {
            throw ValidationException::withMessages(['token' => 'A voter token is required.']);
        }

        return $token;
    }

    /** Lenient: a public ballot read is allowed without one. */
    private function tokenOrNull(Request $request): ?string
    {
        $token = trim((string) $request->header('X-Vote-Token'));

        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        return $token;
    }
}
