import Image from "next/image";
import Link from "next/link";
import FallbackImg from "@/public/images/FIRST.jpeg";
import { getLandingContent } from "@/lib/landing";

export const dynamic = "force-dynamic";

// First page — the "enter the energy" splash. Copy and background come from the
// admin Site settings (Landing splash); the button leads to the festival page.
export default async function Home() {
  const landing = await getLandingContent();

  return (
    <main className="relative w-full h-screen overflow-hidden">
      <Image
        src={landing.imageUrl ?? FallbackImg}
        alt=""
        fill
        priority
        sizes="100vw"
        className="object-cover object-[50%_10%]"
      />

      {/* Darker at the top (title) and bottom (button) for legibility, clear in
          the middle so the figure reads through. */}
      <div className="absolute inset-0 bg-gradient-to-b from-black/60 via-black/15 to-black/55" />

      {(landing.title || landing.subtitle) && (
        <div className="absolute inset-x-0 top-0 z-10 flex flex-col items-center px-4 pt-10 text-center md:pt-14 lg:pt-16">
          {landing.title && (
            <h1 className="font-heading text-[8.5vw] md:text-[5.5vw] lg:text-[4rem] xl:text-[4.75rem] font-extrabold uppercase tracking-tight whitespace-nowrap text-white drop-shadow-[0_2px_20px_rgba(0,0,0,0.7)]">
              {landing.title}
            </h1>
          )}
          {landing.subtitle && (
            <p className="font-heading text-[3.4vw] md:text-[2vw] lg:text-[1.55rem] xl:text-[1.8rem] font-bold uppercase tracking-wide whitespace-nowrap text-white mt-1 md:mt-2 drop-shadow-[0_2px_14px_rgba(0,0,0,0.65)]">
              {landing.subtitle}
            </p>
          )}
        </div>
      )}

      {landing.buttonLabel && (
        <div className="absolute inset-x-0 top-[58%] z-10 flex justify-center px-4">
          <Link
            href="/dashboard/festival"
            className="ts-ticket-pulse group inline-flex items-center gap-2 rounded-full bg-yellow-300 px-5 py-2.5 font-heading text-xs md:text-sm lg:text-base font-extrabold uppercase tracking-wide whitespace-nowrap text-black transition-all duration-300 hover:scale-105 hover:bg-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-yellow-300/60 sm:px-6 sm:py-3"
          >
            <span>{landing.buttonLabel}</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth={2.5}
              strokeLinecap="round"
              strokeLinejoin="round"
              aria-hidden="true"
              className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
            >
              <path d="M5 12h14" />
              <path d="m13 6 6 6-6 6" />
            </svg>
          </Link>
        </div>
      )}
    </main>
  );
}
