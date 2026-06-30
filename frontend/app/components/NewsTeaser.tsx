import Image from "next/image";
import Link from "next/link";
import type { NewsCard } from "@/lib/nav";

type Props = {
  posts: NewsCard[];
  heading: string;
  viewAllLabel: string;
};

function formatDate(value?: string | null) {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

/**
 * "Latest news" teaser for the festival landing: the most recent posts with a
 * link through to the full /news list. Renders nothing when there are no posts.
 */
export default function NewsTeaser({ posts, heading, viewAllLabel }: Props) {
  if (!posts.length) return null;

  return (
    <section className="relative w-full overflow-hidden border-t border-white/5 bg-black px-6 py-20 text-white md:py-28">
      {/* Ambient background glow */}
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_70%)]" />

      <div className="relative z-10 mx-auto w-full max-w-6xl">
        <div className="mb-12 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex flex-col gap-1">
            <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-300">
              Latest Happenings
            </p>
            <h2 className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white md:text-3xl">
              {heading}
            </h2>
          </div>
          <Link
            href="/news"
            className="font-heading group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/50 transition duration-300 hover:text-yellow-300"
          >
            <span>{viewAllLabel}</span>
            <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-1.5">
              →
            </span>
          </Link>
        </div>

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {posts.map((post) => (
            <Link
              key={post.id}
              href={`/news/${post.slug}`}
              className="group relative flex flex-col overflow-hidden rounded-2xl border border-white/5 bg-white/[0.01] transition-all duration-500 hover:-translate-y-1.5 hover:border-yellow-300/30 hover:bg-white/[0.03] hover:shadow-[0_15px_30px_rgba(0,0,0,0.5)]"
            >
              <div className="relative aspect-[3/2] w-full overflow-hidden bg-white/[0.02]">
                {post.coverUrl ? (
                  <>
                    <Image
                      src={post.coverUrl}
                      alt={post.title}
                      fill
                      className="object-cover transition-all duration-700 ease-out group-hover:scale-105"
                      sizes="(max-width: 640px) 100vw, 33vw"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-80 transition-opacity duration-500 group-hover:opacity-30" />
                  </>
                ) : (
                  <div className="flex h-full w-full items-center justify-center border-b border-white/5 text-[11px] font-semibold uppercase tracking-[0.3em] text-white/20">
                    Tbilisi Style 21
                  </div>
                )}
              </div>
              <div className="flex flex-1 flex-col p-5">
                {post.publishedAt ? (
                  <div className="mb-2.5 flex items-center gap-2">
                    <span className="h-1.5 w-1.5 rounded-full bg-yellow-300 animate-pulse" />
                    <span className="text-[10px] font-bold uppercase tracking-[0.2em] text-white/40">
                      {formatDate(post.publishedAt)}
                    </span>
                  </div>
                ) : null}
                <h3 className="font-heading text-base font-extrabold uppercase leading-snug tracking-wide text-white transition-colors duration-300 group-hover:text-yellow-300">
                  {post.title}
                </h3>
                {post.excerpt ? (
                  <p className="mt-3 line-clamp-2 text-xs leading-relaxed text-white/50">
                    {post.excerpt}
                  </p>
                ) : null}
                <div className="mt-auto pt-5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-yellow-300/0 opacity-0 transition-all duration-500 transform translate-y-1 group-hover:text-yellow-300 group-hover:opacity-100 group-hover:translate-y-0">
                  <span>Read Article</span>
                  <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-0.5">
                    →
                  </span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
