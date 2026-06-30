"use client";

import { useEffect, useRef, useState } from "react";

type Props = {
  src: string;
  title: string | null;
  loop: boolean;
};

/**
 * Floating background-music player for the festival landing page. Autoplay with
 * sound is blocked by browsers, so playback always starts from the visitor's
 * click — the button toggles play/pause.
 */
export default function FestivalMusic({ src, title, loop }: Props) {
  const audioRef = useRef<HTMLAudioElement>(null);
  const [playing, setPlaying] = useState(false);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;
    const onEnded = () => setPlaying(false);
    audio.addEventListener("ended", onEnded);
    return () => audio.removeEventListener("ended", onEnded);
  }, []);

  const toggle = async () => {
    const audio = audioRef.current;
    if (!audio) return;
    if (audio.paused) {
      try {
        await audio.play();
        setPlaying(true);
      } catch {
        setPlaying(false);
      }
    } else {
      audio.pause();
      setPlaying(false);
    }
  };

  return (
    <div className="fixed bottom-5 right-5 z-50 flex items-center gap-3">
      <audio ref={audioRef} src={src} loop={loop} preload="none" />

      {title ? (
        <span className="hidden max-w-[40vw] truncate rounded-full bg-black/60 px-3 py-1.5 text-xs uppercase tracking-[0.16em] text-white/80 backdrop-blur-sm sm:inline">
          {playing ? `♪ ${title}` : title}
        </span>
      ) : null}

      <button
        type="button"
        onClick={toggle}
        aria-label={playing ? "Pause music" : "Play music"}
        aria-pressed={playing}
        className="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white shadow-lg ring-1 ring-white/30 backdrop-blur-md transition-all duration-300 hover:scale-105 hover:bg-yellow-300 hover:text-black"
      >
        {playing ? (
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <rect x="6" y="5" width="4" height="14" rx="1" />
            <rect x="14" y="5" width="4" height="14" rx="1" />
          </svg>
        ) : (
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M8 5.14v13.72a1 1 0 0 0 1.54.84l10.29-6.86a1 1 0 0 0 0-1.68L9.54 4.3A1 1 0 0 0 8 5.14Z" />
          </svg>
        )}
      </button>
    </div>
  );
}
