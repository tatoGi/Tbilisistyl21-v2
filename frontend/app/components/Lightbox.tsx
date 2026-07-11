"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
  type ReactNode,
} from "react";
import Image from "next/image";

export type LightboxImage = { src: string; caption?: string };

type LightboxCtx = { open: (index: number) => void };

const Ctx = createContext<LightboxCtx | null>(null);

export function useLightbox() {
  return useContext(Ctx);
}

/**
 * Wraps CMS page content and renders a full-screen image viewer. Every image on
 * the page is registered (in visual order) via `images`; a `LightboxTrigger`
 * opens the overlay at its index and the user can page through with arrows,
 * keyboard, or swipe.
 */
export function LightboxProvider({
  images,
  children,
}: {
  images: LightboxImage[];
  children: ReactNode;
}) {
  const [index, setIndex] = useState<number | null>(null);
  const count = images.length;
  const isOpen = index !== null && count > 0;

  const open = useCallback((i: number) => setIndex(i), []);
  const close = useCallback(() => setIndex(null), []);
  const next = useCallback(
    () => setIndex((i) => (i === null ? i : (i + 1) % count)),
    [count],
  );
  const prev = useCallback(
    () => setIndex((i) => (i === null ? i : (i - 1 + count) % count)),
    [count],
  );

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

  const touchStartX = useRef<number | null>(null);
  const onTouchStart = (e: React.TouchEvent) => {
    touchStartX.current = e.touches[0]?.clientX ?? null;
  };
  const onTouchEnd = (e: React.TouchEvent) => {
    const start = touchStartX.current;
    touchStartX.current = null;
    if (start === null) return;
    const dx = (e.changedTouches[0]?.clientX ?? start) - start;
    if (Math.abs(dx) < 40) return;
    if (dx < 0) next();
    else prev();
  };

  const current = index !== null ? images[index] : null;

  return (
    <Ctx.Provider value={{ open }}>
      {children}
      {isOpen && current ? (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          onClick={close}
          onTouchStart={onTouchStart}
          onTouchEnd={onTouchEnd}
        >
          <button
            type="button"
            onClick={close}
            aria-label="Close"
            className="absolute right-4 top-4 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20"
          >
            &times;
          </button>

          {count > 1 ? (
            <>
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  prev();
                }}
                aria-label="Previous"
                className="absolute left-3 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-3xl text-white transition hover:bg-white/20 sm:left-6"
              >
                &#8249;
              </button>
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  next();
                }}
                aria-label="Next"
                className="absolute right-3 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-3xl text-white transition hover:bg-white/20 sm:right-6"
              >
                &#8250;
              </button>
            </>
          ) : null}

          <div
            className="relative flex h-[82vh] w-full max-w-5xl items-center justify-center px-4"
            onClick={(e) => e.stopPropagation()}
          >
            <Image
              key={current.src}
              src={current.src}
              alt={current.caption || ""}
              fill
              className="object-contain"
              sizes="90vw"
              priority
            />
          </div>

          <div className="pointer-events-none absolute bottom-6 left-1/2 z-10 -translate-x-1/2 text-center">
            {current.caption ? (
              <p className="mb-1 text-sm italic text-white/70">{current.caption}</p>
            ) : null}
            {count > 1 ? (
              <p className="text-xs tracking-widest text-white/50">
                {(index ?? 0) + 1} / {count}
              </p>
            ) : null}
          </div>
        </div>
      ) : null}
    </Ctx.Provider>
  );
}

/** Clickable wrapper that opens the page lightbox at `index`. */
export function LightboxTrigger({
  index,
  className,
  children,
}: {
  index: number;
  className?: string;
  children: ReactNode;
}) {
  const ctx = useLightbox();
  if (!ctx) return <>{children}</>;
  return (
    <button
      type="button"
      onClick={() => ctx.open(index)}
      aria-label="View image"
      className={`block w-full cursor-zoom-in ${className ?? ""}`}
    >
      {children}
    </button>
  );
}
