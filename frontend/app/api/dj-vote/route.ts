import { cookies } from "next/headers";
import { NextResponse } from "next/server";
import { djVoteBackendUrl } from "@/lib/dj-vote";
import { resolveVoteToken, VOTE_TOKEN_COOKIE } from "@/lib/vote-token";

const ONE_YEAR_SECONDS = 60 * 60 * 24 * 365;

/**
 * Owns the first-party voter cookie so Laravel can stay stateless and we avoid
 * cross-site cookie rules between :3000 and :8000.
 */
async function withToken(): Promise<{ token: string; isNew: boolean }> {
  const store = await cookies();
  return resolveVoteToken(store.get(VOTE_TOKEN_COOKIE)?.value);
}

function attachCookie(response: NextResponse, token: string, isNew: boolean): NextResponse {
  if (isNew) {
    response.cookies.set(VOTE_TOKEN_COOKIE, token, {
      httpOnly: true,
      sameSite: "lax",
      path: "/",
      maxAge: ONE_YEAR_SECONDS,
      secure: process.env.NODE_ENV === "production",
    });
  }

  return response;
}

export async function GET() {
  const { token, isNew } = await withToken();

  const upstream = await fetch(`${djVoteBackendUrl()}/api/dj-vote`, {
    headers: { Accept: "application/json", "X-Vote-Token": token },
    cache: "no-store",
  });

  const body = await upstream.json().catch(() => null);

  return attachCookie(NextResponse.json(body, { status: upstream.status }), token, isNew);
}

export async function POST(request: Request) {
  const { token, isNew } = await withToken();
  const payload = await request.json().catch(() => ({}));

  const upstream = await fetch(`${djVoteBackendUrl()}/api/dj-vote`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Vote-Token": token,
    },
    body: JSON.stringify({ djId: payload?.djId }),
    cache: "no-store",
  });

  const body = await upstream.json().catch(() => null);

  return attachCookie(NextResponse.json(body, { status: upstream.status }), token, isNew);
}
