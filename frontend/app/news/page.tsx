import type { Metadata } from "next";
import { listNews } from "@/lib/posts";
import NewsCardGrid from "@/app/components/NewsCardGrid";
import Pagination from "@/app/components/Pagination";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "News — Tbilisi Style 21",
};

const PER_PAGE = 9;

type PageProps = { searchParams: Promise<{ page?: string }> };

export default async function NewsPage({ searchParams }: PageProps) {
  const allPosts = await listNews();
  const { page } = await searchParams;
  const totalPages = Math.max(1, Math.ceil(allPosts.length / PER_PAGE));
  const current = Math.min(Math.max(1, Number(page) || 1), totalPages);
  const posts = allPosts.slice((current - 1) * PER_PAGE, current * PER_PAGE);

  return (
    <main className="relative min-h-screen w-full overflow-hidden bg-[#0b0906] px-6 pb-20 pt-28 text-[color:var(--ts-body)] md:pb-28 md:pt-32">
      <div className="pointer-events-none absolute left-1/2 top-[-200px] z-0 h-[600px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(232,184,75,0.25)_0%,transparent_70%)] opacity-35" />

      <div className="relative z-10 mx-auto w-full max-w-6xl">
        <div className="mb-12">
          <div className="mb-2 flex items-center gap-3">
            <span className="h-[2px] w-[34px] bg-[#e8b84b]" />
            <p className="font-unbounded text-[13px] font-bold uppercase tracking-[0.22em] text-[#e8b84b]">
              Tbilisi Style 21
            </p>
          </div>
          <h1 className="font-unbounded text-[clamp(2.2rem,5vw,4rem)] font-extrabold tracking-[-0.01em] text-[color:var(--ts-head)]">
            სიახლეები
          </h1>
        </div>

        {allPosts.length === 0 ? (
          <p className="text-white/50">No news yet.</p>
        ) : (
          <>
            <NewsCardGrid posts={posts} variant="redesign" />
            <Pagination basePath="/news" currentPage={current} totalPages={totalPages} />
          </>
        )}
      </div>
    </main>
  );
}
