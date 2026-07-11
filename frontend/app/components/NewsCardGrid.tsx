import Image from "next/image";
import Link from "next/link";
import { formatNewsDate } from "@/lib/posts";

export type NewsCardItem = {
  id: string;
  title: string;
  slug: string;
  coverUrl: string | null;
  excerpt: string | null;
  publishedAt: string | null;
};

type Props = {
  posts: NewsCardItem[];
  /**
   * "default" keeps the original festival-teaser styling (used by NewsTeaser on
   * the festival page, which must stay unchanged). "redesign" applies the new
   * gold/Unbounded look for the /news page.
   */
  variant?: "default" | "redesign";
};

/** Card grid for news lists — shared by the festival teaser and /news page. */
export default function NewsCardGrid({ posts, variant = "default" }: Props) {
  const rd = variant === "redesign";
  const card = rd
    ? "group relative flex flex-col overflow-hidden rounded-[20px] border border-white/10 bg-white/[0.02] transition-all duration-500 hover:-translate-y-1.5 hover:border-[#e8b84b]/30 hover:bg-white/[0.04] hover:shadow-[0_15px_30px_rgba(0,0,0,0.5)]"
    : "group relative flex flex-col overflow-hidden rounded-2xl border border-white/5 bg-white/[0.01] transition-all duration-500 hover:-translate-y-1.5 hover:border-yellow-300/30 hover:bg-white/[0.03] hover:shadow-[0_15px_30px_rgba(0,0,0,0.5)]";
  const dot = rd ? "bg-[#e8b84b]" : "bg-yellow-300";
  const title = rd
    ? "font-unbounded text-lg font-extrabold leading-snug tracking-tight text-[color:var(--ts-head)] transition-colors duration-300 group-hover:text-[#e8b84b]"
    : "font-heading text-base font-extrabold uppercase leading-snug tracking-wide text-white transition-colors duration-300 group-hover:text-yellow-300";
  const read = rd
    ? "mt-auto flex translate-y-1 transform items-center gap-1.5 pt-5 text-[10px] font-bold uppercase tracking-[0.2em] text-[#e8b84b]/0 opacity-0 transition-all duration-500 group-hover:translate-y-0 group-hover:text-[#e8b84b] group-hover:opacity-100"
    : "mt-auto flex translate-y-1 transform items-center gap-1.5 pt-5 text-[10px] font-bold uppercase tracking-[0.2em] text-yellow-300/0 opacity-0 transition-all duration-500 group-hover:translate-y-0 group-hover:text-yellow-300 group-hover:opacity-100";

  return (
    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {posts.map((post) => (
        <Link key={post.id} href={`/news/${post.slug}`} className={card}>
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
                <span className={`h-1.5 w-1.5 animate-pulse rounded-full ${dot}`} />
                <span className="text-[10px] font-bold uppercase tracking-[0.2em] text-white/40">
                  {formatNewsDate(post.publishedAt)}
                </span>
              </div>
            ) : null}
            <h3 className={title}>{post.title}</h3>
            {post.excerpt ? (
              <p className="mt-3 line-clamp-2 text-xs leading-relaxed text-white/50">{post.excerpt}</p>
            ) : null}
            <div className={read}>
              <span>Read Article</span>
              <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-0.5">
                →
              </span>
            </div>
          </div>
        </Link>
      ))}
    </div>
  );
}
