import { describe, expect, it } from "vitest";
import { resolveVoteToken, VOTE_TOKEN_COOKIE } from "./vote-token";

describe("resolveVoteToken", () => {
  it("mints a new token when none exists", () => {
    const { token, isNew } = resolveVoteToken(undefined);

    expect(isNew).toBe(true);
    expect(token).toHaveLength(36);
  });

  it("reuses an existing token unchanged", () => {
    const existing = "123e4567-e89b-12d3-a456-426614174000";

    expect(resolveVoteToken(existing)).toEqual({ token: existing, isNew: false });
  });

  it("replaces a malformed token", () => {
    const { token, isNew } = resolveVoteToken("not-a-uuid");

    expect(isNew).toBe(true);
    expect(token).not.toBe("not-a-uuid");
  });

  it("replaces an empty token", () => {
    expect(resolveVoteToken("").isNew).toBe(true);
  });

  it("replaces a null token", () => {
    expect(resolveVoteToken(null).isNew).toBe(true);
  });

  it("mints a distinct token on each call", () => {
    expect(resolveVoteToken(undefined).token).not.toBe(resolveVoteToken(undefined).token);
  });

  it("exposes the cookie name", () => {
    expect(VOTE_TOKEN_COOKIE).toBe("dj_vote_token");
  });
});
