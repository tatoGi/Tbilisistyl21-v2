"use client";

import { useEffect, useState } from "react";
import { mediaUrl, t } from "@/lib/utils";
import type { DjVoteState } from "@/lib/types";

type Props = { initial: DjVoteState; locale: string };

function remainingLabel(endsAt: string): string | null {
  const ms = new Date(endsAt).getTime() - Date.now();
  if (Number.isNaN(ms) || ms <= 0) return null;

  const totalMinutes = Math.floor(ms / 60000);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  return hours > 0 ? `${hours}სთ ${minutes}წთ` : `${minutes}წთ`;
}

/** Ticks once a minute — the round closes on the server, this is only a hint. */
function Countdown({ endsAt }: { endsAt: string }) {
  const [label, setLabel] = useState<string | null>(() => remainingLabel(endsAt));

  useEffect(() => {
    const id = setInterval(() => setLabel(remainingLabel(endsAt)), 60_000);
    return () => clearInterval(id);
  }, [endsAt]);

  if (!label) return null;

  return (
    <p className="mt-2 text-center text-xs uppercase tracking-widest text-amber-400">
      დარჩა {label}
    </p>
  );
}

export default function DjVote({ initial, locale }: Props) {
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
        setError("ხმის მიცემა დასრულებულია.");
        const refreshed = await fetch("/api/dj-vote").then((r) => r.json());
        setState(refreshed as DjVoteState);
        return;
      }

      if (!res.ok) {
        setError("ხმის მიცემა ვერ მოხერხდა. სცადე თავიდან.");
        return;
      }

      setState((await res.json()) as DjVoteState);
    } catch {
      setError("კავშირის შეცდომა. სცადე თავიდან.");
    } finally {
      setPending(null);
    }
  }

  return (
    <section className="w-full bg-black px-4 py-16 text-white sm:px-8">
      <div className="mx-auto max-w-5xl">
        <h2 className="text-center text-2xl font-bold uppercase tracking-widest sm:text-3xl">
          ვინ დაუკრავს?
        </h2>
        <p className="mt-3 text-center text-sm text-white/60">
          {state.hasVoted
            ? "შენი ხმა დაფიქსირდა — შეგიძლია შეცვალო."
            : "აირჩიე დიჯეი და ნახე შედეგები."}
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
                    ) : null}
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
                          {votes} ხმა · {percent}%
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
