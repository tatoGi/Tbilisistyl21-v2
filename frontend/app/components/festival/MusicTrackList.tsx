"use client";

import { useRef, useState, useCallback, useEffect } from "react";
import { useTranslations } from "next-intl";
import type { MusicTrack } from "@/lib/music-tracks";

type Props = {
  tracks: MusicTrack[];
};

function formatTime(sec: number) {
  const m = Math.floor(sec / 60);
  const s = Math.floor(sec % 60);
  return `${m}:${s.toString().padStart(2, "0")}`;
}

export default function MusicTrackList({ tracks }: Props) {
  const t = useTranslations();
  const audioRef = useRef<HTMLAudioElement>(null);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [playing, setPlaying] = useState(false);
  const [open, setOpen] = useState(false);
  const [progress, setProgress] = useState(0);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const attemptedAutoplay = useRef(false);

  const current = tracks[currentIndex] ?? null;

  // Autoplay on mount
  useEffect(() => {
    if (attemptedAutoplay.current || !tracks.length) return;
    attemptedAutoplay.current = true;
    const audio = audioRef.current;
    if (!audio) return;
    audio.src = tracks[0].audioUrl;
    audio.play().then(() => setPlaying(true)).catch(() => setPlaying(false));
  }, [tracks]);

  // Track time updates
  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;
    const onTimeUpdate = () => {
      setCurrentTime(audio.currentTime);
      if (audio.duration && Number.isFinite(audio.duration)) {
        setProgress((audio.currentTime / audio.duration) * 100);
      }
    };
    const onLoaded = () => {
      if (audio.duration && Number.isFinite(audio.duration)) {
        setDuration(audio.duration);
      }
    };
    audio.addEventListener("timeupdate", onTimeUpdate);
    audio.addEventListener("loadedmetadata", onLoaded);
    audio.addEventListener("durationchange", onLoaded);
    return () => {
      audio.removeEventListener("timeupdate", onTimeUpdate);
      audio.removeEventListener("loadedmetadata", onLoaded);
      audio.removeEventListener("durationchange", onLoaded);
    };
  }, []);

  const playTrack = useCallback(
    (index: number) => {
      const audio = audioRef.current;
      if (!audio || !tracks[index]) return;
      audio.src = tracks[index].audioUrl;
      setProgress(0);
      setCurrentTime(0);
      setDuration(0);
      audio.play().then(() => {
        setCurrentIndex(index);
        setPlaying(true);
      }).catch(() => setPlaying(false));
    },
    [tracks],
  );

  const toggle = useCallback(() => {
    const audio = audioRef.current;
    if (!audio || !current) return;
    if (playing) {
      audio.pause();
      setPlaying(false);
    } else {
      if (!audio.src || audio.src !== current.audioUrl) {
        audio.src = current.audioUrl;
      }
      audio.play().then(() => setPlaying(true)).catch(() => setPlaying(false));
    }
  }, [playing, current]);

  const seek = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    const audio = audioRef.current;
    if (!audio || !audio.duration) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const pct = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    audio.currentTime = pct * audio.duration;
  }, []);

  const prevTrack = useCallback(() => {
    const prev = (currentIndex - 1 + tracks.length) % tracks.length;
    playTrack(prev);
  }, [currentIndex, tracks.length, playTrack]);

  const nextTrack = useCallback(() => {
    const next = (currentIndex + 1) % tracks.length;
    playTrack(next);
  }, [currentIndex, tracks.length, playTrack]);

  const onEnded = useCallback(() => {
    nextTrack();
  }, [nextTrack]);

  if (!tracks.length) return null;

  return (
    <>
      <audio ref={audioRef} onEnded={onEnded} preload="none" />

      {/* On mobile the wide player bar is raised above the bottom-right "buy
          tickets" CTA so the two floating widgets don't overlap; on sm+ the
          screen is wide enough for the opposite corners to sit side by side. */}
      <div className="fixed bottom-24 left-5 z-40 flex flex-col items-start gap-2 sm:bottom-7 sm:left-7">
        {/* Expanded track list */}
        {open && (
          <div className="mb-1 max-h-64 w-72 overflow-y-auto rounded-xl bg-black/90 p-2 shadow-2xl ring-1 ring-white/10 backdrop-blur-md sm:w-80">
            {tracks.map((track, i) => {
              const isActive = currentIndex === i;
              const isPlaying = isActive && playing;
              return (
                <button
                  key={track.id}
                  type="button"
                  onClick={() => {
                    playTrack(i);
                    setOpen(false);
                  }}
                  className={`flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors ${
                    isActive ? "bg-[#e8b84b]/15" : "hover:bg-white/10"
                  }`}
                >
                  <span
                    className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                      isActive
                        ? "bg-[#e8b84b] text-black"
                        : "bg-white/10 text-white/50"
                    }`}
                  >
                    {isPlaying ? (
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="4" y="2" width="4" height="20" rx="1" />
                        <rect x="16" y="2" width="4" height="20" rx="1" />
                      </svg>
                    ) : (
                      i + 1
                    )}
                  </span>
                  <span className="min-w-0 flex-1">
                    <span
                      className={`block truncate text-sm font-medium ${
                        isActive ? "text-[#e8b84b]" : "text-white"
                      }`}
                    >
                      {track.title}
                    </span>
                    {track.artist ? (
                      <span className="block truncate text-xs text-white/50">
                        {track.artist}
                      </span>
                    ) : null}
                  </span>
                </button>
              );
            })}
          </div>
        )}

        {/* Mini player bar — collapses to just the play button on mobile. */}
        <div className="w-auto rounded-full bg-black/90 shadow-2xl ring-1 ring-white/10 backdrop-blur-md sm:w-80 sm:rounded-2xl">
          {/* Track info row */}
          <div className="flex items-center gap-3 p-1.5 sm:px-3 sm:pt-3 sm:pb-1">
            {/* Prev */}
            <button
              type="button"
              onClick={prevTrack}
              aria-label={t("a11y.previousTrack")}
              className="hidden shrink-0 text-white/50 transition-colors hover:text-white sm:block"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M6 6a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1Zm13.52 4.16-9-6A1 1 0 0 0 9 5v14a1 1 0 0 0 1.52.84l9-6a1 1 0 0 0 0-1.68Z" transform="scale(-1,1) translate(-24,0)" />
              </svg>
            </button>

            {/* Play/Pause */}
            <button
              type="button"
              onClick={toggle}
              aria-label={playing ? t("a11y.pause") : t("a11y.play")}
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8b84b] text-black transition-transform duration-150 hover:scale-105 hover:bg-white"
            >
              {playing ? (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <rect x="6" y="5" width="4" height="14" rx="1" />
                  <rect x="14" y="5" width="4" height="14" rx="1" />
                </svg>
              ) : (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                  <path d="M8 5.14v13.72a1 1 0 0 0 1.54.84l10.29-6.86a1 1 0 0 0 0-1.68L9.54 4.3A1 1 0 0 0 8 5.14Z" />
                </svg>
              )}
            </button>

            {/* Next */}
            <button
              type="button"
              onClick={nextTrack}
              aria-label={t("a11y.nextTrack")}
              className="hidden shrink-0 text-white/50 transition-colors hover:text-white sm:block"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18 6a1 1 0 0 1 1 1v10a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1ZM4.48 10.16l9-6A1 1 0 0 1 15 5v14a1 1 0 0 1-1.52.84l-9-6a1 1 0 0 1 0-1.68Z" />
              </svg>
            </button>

            {/* Title + artist */}
            <button
              type="button"
              onClick={() => setOpen((o) => !o)}
              className="hidden min-w-0 flex-1 text-left sm:block"
            >
              {current ? (
                <>
                  <span className="block truncate text-xs font-semibold text-white">
                    {current.title}
                  </span>
                  {current.artist ? (
                    <span className="block truncate text-[10px] text-white/50">
                      {current.artist}
                    </span>
                  ) : null}
                </>
              ) : null}
            </button>

            {/* Track list toggle */}
            <button
              type="button"
              onClick={() => setOpen((o) => !o)}
              aria-label={open ? t("a11y.closeTrackList") : t("a11y.openTrackList")}
              className="hidden shrink-0 text-white/40 transition-colors hover:text-white sm:block"
            >
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="currentColor"
                className={`transition-transform ${open ? "rotate-180" : ""}`}
                aria-hidden="true"
              >
                <path d="M12 8l-6 6h12l-6-6z" />
              </svg>
            </button>
          </div>

          {/* Progress bar + times — desktop only (mobile shows just the play button) */}
          <div className="hidden px-3 pb-3 pt-1 sm:block">
            <div
              className="group relative h-1.5 cursor-pointer rounded-full bg-white/10"
              onClick={seek}
            >
              <div
                className="absolute inset-y-0 left-0 rounded-full bg-[#e8b84b] transition-[width] duration-100"
                style={{ width: `${progress}%` }}
              />
              <div
                className="absolute top-1/2 -translate-y-1/2 h-3 w-3 rounded-full bg-[#e8b84b] opacity-0 shadow transition-opacity group-hover:opacity-100"
                style={{ left: `${progress}%`, marginLeft: -6 }}
              />
            </div>
            <div className="mt-1 flex justify-between text-[10px] text-white/40">
              <span>{formatTime(currentTime)}</span>
              <span>{duration ? formatTime(duration) : "--:--"}</span>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
