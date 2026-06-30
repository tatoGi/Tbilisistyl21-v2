import { NextRequest, NextResponse } from "next/server";
import { isLocale, localeCookieName } from "@/i18n/config";

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
  });

  return response;
}
