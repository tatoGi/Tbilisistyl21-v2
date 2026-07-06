import Link from "next/link";
import type { NewsCard } from "@/lib/nav";
import NewsCardGrid from "./NewsCardGrid";

type Props = {
  posts: NewsCard[];
  heading: string;
  viewAllLabel: string;
};

/**
 * "Latest news" teaser for the festival landing: the most recent posts with a
 * link through to the full /news list. Renders nothing when there are no posts.
 */
export default function NewsTeaser({ posts, heading, viewAllLabel }: Props) {
  if (!posts.length) return null;

  return (
    <section className="relative w-full overflow-hidden border-t border-white/5 bg-black px-6 py-20 text-white md:py-28">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_70%)]" />

      <div className="relative z-10 mx-auto w-full max-w-6xl">
        <div className="mb-12 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          {heading || viewAllLabel ? (
            <>
              {heading ? (
                <div className="flex flex-col gap-1">
                  <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-300">
                    Tbilisi Style 21
                  </p>
                  <h2 className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white md:text-3xl">
                    {heading}
                  </h2>
                </div>
              ) : (
                <div />
              )}
              {viewAllLabel ? (
                <Link
                  href="/news"
                  className="font-heading group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/50 transition duration-300 hover:text-yellow-300"
                >
                  <span>{viewAllLabel}</span>
                  <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-1.5">
                    →
                  </span>
                </Link>
              ) : null}
            </>
          ) : null}
        </div>

        <NewsCardGrid posts={posts} />
      </div>
    </section>
  );
}
