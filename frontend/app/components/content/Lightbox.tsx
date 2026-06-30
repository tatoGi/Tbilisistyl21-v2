"use client";

import Image from "next/image";
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";

export type LightboxImage = { src: string; alt: string };

type LightboxCtx = { open: (src: string) => void };

const Ctx = createContext<LightboxCtx | null>(null);

/**
 * Fancybox-style image viewer. Wraps a page's content and exposes `open(src)`
 * via context; any <LightboxTrigger> inside opens the fullscreen viewer at that
 * image, with prev/next slider, keyboard nav and a counter.
 */
export function LightboxProvider({
  images,
  children,
}: {
  images: LightboxImage[];
  children: ReactNode;
}) {
  const [index, setIndex] = useState<number | null>(null);
  const isOpen = index !== null;
  const count = images.length;

  const open = useCallback(
    (src: string) => {
      const i = images.findIndex((im) => im.src === src);
      setIndex(i >= 0 ? i : 0);
    },
    [images],
  );

  const close = useCallback(() => setIndex(null), []);
  const next = useCallback(
    () => setIndex((i) => (i === null ? i : (i + 1) % count)),
    [count],
  );
  const prev = useCallback(
    () => setIndex((i) => (i === null ? i : (i - 1 + count) % count)),
    [count],
  );

  // Keyboard navigation + scroll lock while the viewer is open.
  useEffect(() => {
    if (!isOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") close();
      else if (e.key === "ArrowRight") next();
      else if (e.key === "ArrowLeft") prev();
    };
    document.addEventListener("keydown", onKey);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prevOverflow;
    };
  }, [isOpen, close, next, prev]);

  const current = index !== null ? images[index] : null;

  return (
    <Ctx.Provider value={{ open }}>
      {children}
      {current ? (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-sm"
          onClick={close}
          role="dialog"
          aria-modal="true"
          aria-label="Image viewer"
        >
          {/* Close */}
          <button
            type="button"
            onClick={close}
            aria-label="Close"
            className="absolute right-4 top-4 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20"
          >
            ✕
          </button>

          {count > 1 ? (
            <>
              {/* Prev */}
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  prev();
                }}
                aria-label="Previous image"
                className="absolute left-3 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-6"
              >
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="m15 18-6-6 6-6" />
                </svg>
              </button>
              {/* Next */}
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  next();
                }}
                aria-label="Next image"
                className="absolute right-3 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-6"
              >
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="m9 18 6-6-6-6" />
                </svg>
              </button>
            </>
          ) : null}

          {/* Image */}
          <div
            className="relative h-[88vh] w-[92vw]"
            onClick={(e) => e.stopPropagation()}
          >
            <Image
              src={current.src}
              alt={current.alt}
              fill
              className="object-contain"
              sizes="92vw"
              priority
            />
          </div>

          {count > 1 ? (
            <p className="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-4 py-1 text-sm font-semibold text-white">
              {(index ?? 0) + 1} / {count}
            </p>
          ) : null}
        </div>
      ) : null}
    </Ctx.Provider>
  );
}

/** Clickable wrapper that opens the lightbox at `src`. */
export function LightboxTrigger({
  src,
  className = "",
  children,
}: {
  src: string;
  className?: string;
  children: ReactNode;
}) {
  const ctx = useContext(Ctx);
  if (!ctx) return <>{children}</>;
  return (
    <button
      type="button"
      onClick={() => ctx.open(src)}
      aria-label="Enlarge image"
      className={`group block cursor-zoom-in ${className}`}
    >
      {children}
    </button>
  );
}
