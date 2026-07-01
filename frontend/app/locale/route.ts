import { NextRequest, NextResponse } from "next/server";
import { isLocale, localeCookieName } from "@/i18n/config";

/** Sets the locale cookie. Lives at /locale (not /api/locale) so nginx can route /api/* to Laravel. */
export async function POST(request: NextRequest) {
  const body = (await request.json().catch(() => null)) as {
    locale?: string;
  } | null;

  if (!isLocale(body?.locale)) {
    return NextResponse.json({ ok: false }, { status: 400 });
  }

  const response = NextResponse.json({ ok: true });

  response.cookies.set(localeCookieName, body.locale, {
    path: "/",
    maxAge: 60 * 60 * 24 * 365,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
  });

  return response;
}
