<?php

namespace App\Services;

use App\Models\Dj;
use App\Models\DjVote;
use App\Models\DjVotingRound;

class DjVoteService
{
    /**
     * Vote totals for a round, highest first.
     *
     * Includes every DJ on the ballot (so zero-vote DJs still appear) plus any
     * DJ holding votes in this round even if they were later unpublished —
     * removing someone from the ballot must not silently drop their votes.
     */
    public function results(DjVotingRound $round): array
    {
        $counts = DjVote::where('round_id', $round->id)
            ->selectRaw('dj_id, COUNT(*) as aggregate')
            ->groupBy('dj_id')
            ->pluck('aggregate', 'dj_id');

        $djIds = $round->djs->pluck('id')
            ->merge($counts->keys())
            ->unique()
            ->values();

        $total = (int) $counts->sum();

        return $djIds
            ->map(function (string $djId) use ($counts, $total) {
                $votes = (int) ($counts[$djId] ?? 0);

                return [
                    'djId' => $djId,
                    'votes' => $votes,
                    // Guard the empty round: the admin widget renders rounds
                    // with no votes at all.
                    'percent' => $total > 0 ? round($votes * 100 / $total, 1) : 0.0,
                ];
            })
            ->sortByDesc('votes')
            ->values()
            ->all();
    }

    /**
     * Record or change this voter's choice. Keyed on (round, token) so a
     * changed vote updates the existing row and the unique index holds.
     */
    public function castVote(DjVotingRound $round, string $djId, string $voterToken, ?string $ip): DjVote
    {
        return DjVote::updateOrCreate(
            ['round_id' => $round->id, 'voter_token' => $voterToken],
            ['dj_id' => $djId, 'ip_hash' => DjVote::hashIp($ip)],
        );
    }
}
