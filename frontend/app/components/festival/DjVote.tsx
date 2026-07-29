"use client";

import { useEffect, useState } from "react";
import { useTranslations } from "next-intl";
import { mediaUrl, t } from "@/lib/utils";
import type { DjVoteState, Translatable } from "@/lib/types";

type Props = { initial: DjVoteState; locale: string };

/**
 * Admin copy for exactly this locale, or nothing.
 *
 * Deliberately not the shared `t()` helper: that falls back to Georgian when a
 * language is blank, which would show Georgian to a Russian visitor. Here a
 * blank language is meant to fall through to the built-in translation instead.
 */
function copyForLocale(field: Translatable | null, locale: string): string {
  return field?.[locale as keyof Translatable]?.trim() ?? "";
}

/** Up to two initials, so a photoless card still reads as that specific DJ. */
function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "?";

  return parts
    .slice(0, 2)
    .map((part) => [...part][0]?.toUpperCase() ?? "")
    .join("");
}

/** Remaining time as raw numbers; the wording is left to the translations. */
function remaining(endsAt: string): { hours: number; minutes: number } | null {
  const ms = new Date(endsAt).getTime() - Date.now();
  if (Number.isNaN(ms) || ms <= 0) return null;

  const totalMinutes = Math.floor(ms / 60000);

  return { hours: Math.floor(totalMinutes / 60), minutes: totalMinutes % 60 };
}

/**
 * Ticks once a minute — the round closes on the server, this is only a hint.
 *
 * Deliberately renders nothing until mounted: the remaining time is derived
 * from `Date.now()`, so computing it during SSR guarantees a hydration
 * mismatch as soon as the clock crosses a minute between the two renders.
 */
function Countdown({ endsAt }: { endsAt: string }) {
  const tr = useTranslations("djVote");
  const [left, setLeft] = useState<{ hours: number; minutes: number } | null>(null);

  useEffect(() => {
    setLeft(remaining(endsAt));

    const id = setInterval(() => setLeft(remaining(endsAt)), 60_000);
    return () => clearInterval(id);
  }, [endsAt]);

  if (!left) return null;

  const time =
    left.hours > 0
      ? tr("hoursMinutes", { hours: left.hours, minutes: left.minutes })
      : tr("minutes", { minutes: left.minutes });

  return (
    <p className="mt-2 text-center text-xs uppercase tracking-widest text-amber-400">
      {tr("remaining", { time })}
    </p>
  );
}

export default function DjVote({ initial, locale }: Props) {
  const tr = useTranslations("djVote");
  const [state, setState] = useState<DjVoteState>(initial);
  const [pending, setPending] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // No open round — the section is absent rather than empty.
  if (!state.round || state.djs.length === 0) return null;

  const votesFor = (djId: string) =>
    state.results?.find((r) => r.djId === djId) ?? { votes: 0, percent: 0 };

  async function vote(djId: string) {
    setPending(djId);
    setError(null);

    try {
      const res = await fetch("/api/dj-vote", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ djId }),
      });

      if (res.status === 409) {
        setError(tr("errorClosed"));
        const refreshed = await fetch("/api/dj-vote").then((r) => r.json());
        setState(refreshed as DjVoteState);
        return;
      }

      if (!res.ok) {
        setError(tr("errorFailed"));
        return;
      }

      setState((await res.json()) as DjVoteState);
    } catch {
      setError(tr("errorNetwork"));
    } finally {
      setPending(null);
    }
  }

  return (
    <section className="w-full bg-black px-4 py-16 text-white sm:px-8">
      <div className="mx-auto max-w-5xl">
        <h2 className="text-center text-2xl font-bold uppercase tracking-widest sm:text-3xl">
          {copyForLocale(state.round.heading, locale) || tr("heading")}
        </h2>
        <p className="mt-3 text-center text-sm text-white/60">
          {state.hasVoted
            ? tr("promptAfterVote")
            : copyForLocale(state.round.subtitle, locale) || tr("promptBeforeVote")}
        </p>

        <Countdown endsAt={state.round.endsAt} />

        {error ? (
          <p className="mt-4 text-center text-sm text-red-400" role="alert">
            {error}
          </p>
        ) : null}

        <ul className="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {state.djs.map((dj) => {
            const chosen = state.votedDjId === dj.id;
            const { votes, percent } = votesFor(dj.id);

            return (
              <li key={dj.id}>
                <button
                  type="button"
                  onClick={() => vote(dj.id)}
                  disabled={pending !== null}
                  aria-pressed={chosen}
                  className={`group w-full overflow-hidden rounded-lg border text-left transition disabled:opacity-60 ${
                    chosen ? "border-amber-400" : "border-white/15 hover:border-white/40"
                  }`}
                >
                  <div className="aspect-square w-full overflow-hidden bg-white/5">
                    {dj.photo ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img
                        src={mediaUrl(dj.photo)}
                        alt={dj.name}
                        className="h-full w-full object-cover"
                      />
                    ) : (
                      // A bare empty square reads as a broken image, so a
                      // photoless DJ gets a deliberate placeholder instead.
                      <div
                        aria-hidden="true"
                        className="flex h-full w-full flex-col items-center justify-center gap-2 bg-gradient-to-br from-white/10 to-transparent"
                      >
                        <span className="text-3xl font-bold tracking-widest text-white/70 sm:text-4xl">
                          {initials(dj.name)}
                        </span>
                        <span className="text-[10px] uppercase tracking-[0.2em] text-amber-400/70">
                          {tr("votePlaceholder")}
                        </span>
                      </div>
                    )}
                  </div>

                  <div className="p-3">
                    <span className="block text-sm font-semibold">{dj.name}</span>
                    {dj.bio ? (
                      <span className="mt-1 block text-xs text-white/50">
                        {t(dj.bio, locale)}
                      </span>
                    ) : null}

                    {state.hasVoted ? (
                      <div className="mt-3">
                        <div className="h-1.5 w-full overflow-hidden rounded bg-white/15">
                          <div
                            className="h-full rounded bg-amber-400"
                            style={{ width: `${percent}%` }}
                          />
                        </div>
                        <span className="mt-1 block text-xs text-white/60">
                          {tr("voteCount", { votes })} · {percent}%
                        </span>
                      </div>
                    ) : null}
                  </div>
                </button>
              </li>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
