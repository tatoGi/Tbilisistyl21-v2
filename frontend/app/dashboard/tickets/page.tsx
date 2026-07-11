import { notFound } from "next/navigation";
import { listTickets } from "@/lib/tickets";
import { getCmsPageTitle } from "@/lib/cms-page-title";
import TicketsClient from "./TicketsClient";

export const dynamic = "force-dynamic";

export default async function TicketsPage() {
  const [tickets, title] = await Promise.all([
    listTickets({ publicOnly: true }),
    getCmsPageTitle("tickets"),
  ]);

  if (!title) notFound();

  return (
    <main className="relative mx-auto min-h-screen w-full max-w-[1440px] overflow-hidden px-5 pb-24 pt-28 text-[color:var(--ts-body)] md:px-10">
      <div className="pointer-events-none absolute left-1/2 top-[-200px] z-0 h-[600px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(232,184,75,0.25)_0%,transparent_70%)] opacity-35" />

      <div className="relative z-10">
        <div className="mb-2 flex items-center gap-3">
          <span className="h-[2px] w-[34px] bg-[#e8b84b]" />
          <p className="font-unbounded text-[13px] font-bold uppercase tracking-[0.22em] text-[#e8b84b]">
            Tbilisi Style 21
          </p>
        </div>
        <h1 className="font-unbounded text-[clamp(2.2rem,5vw,4.25rem)] font-extrabold tracking-[-0.01em] text-[color:var(--ts-head)]">
          {title}
        </h1>
        <p className="mt-4 max-w-[640px] text-[17px] leading-relaxed text-[color:var(--ts-muted)]">
          აირჩიე ფორმატი, რომელიც შენ გერგება — ერთი ღამიდან სრულ ფესტივალამდე.
        </p>
      </div>

      <div className="relative z-10">
        <TicketsClient tickets={tickets} />
      </div>
    </main>
  );
}
