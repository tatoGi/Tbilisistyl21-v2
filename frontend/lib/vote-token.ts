export const VOTE_TOKEN_COOKIE = "dj_vote_token";

const UUID_PATTERN =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/**
 * The anonymous voter identity. A malformed or missing value is replaced
 * rather than forwarded, so a corrupted cookie cannot wedge a visitor out of
 * voting — the cost is that they are treated as a new voter.
 */
export function resolveVoteToken(
  existing?: string | null,
): { token: string; isNew: boolean } {
  if (existing && UUID_PATTERN.test(existing)) {
    return { token: existing, isNew: false };
  }

  return { token: crypto.randomUUID(), isNew: true };
}
