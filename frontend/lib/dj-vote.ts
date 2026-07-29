import { cookies } from "next/headers";
import { resolveVoteToken, VOTE_TOKEN_COOKIE } from "./vote-token";
import type { DjVoteState } from "./types";

const EMPTY: DjVoteState = {
  round: null,
  djs: [],
  hasVoted: false,
  votedDjId: null,
  results: null,
};

/** Both callers here run server-side only, so the internal URL always wins. */
export function djVoteBackendUrl(): string {
  return (
    process.env.API_INTERNAL_URL ||
    process.env.NEXT_PUBLIC_API_URL ||
    "http://localhost:8000"
  );
}

/**
 * Initial voting state for the festival page. A read-only server component
 * cannot set cookies, so an unseen visitor is fetched with a throwaway token —
 * the route handler mints and persists the real one on their first interaction.
 */
export async function getDjVoteState(): Promise<DjVoteState> {
  try {
    const store = await cookies();
    const { token } = resolveVoteToken(store.get(VOTE_TOKEN_COOKIE)?.value);

    const res = await fetch(`${djVoteBackendUrl()}/api/dj-vote`, {
      headers: { Accept: "application/json", "X-Vote-Token": token },
      cache: "no-store",
    });

    if (!res.ok) return EMPTY;

    return (await res.json()) as DjVoteState;
  } catch {
    return EMPTY;
  }
}
