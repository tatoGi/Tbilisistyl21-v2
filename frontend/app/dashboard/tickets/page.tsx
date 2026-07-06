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
    <main className="relative mx-auto min-h-screen w-full max-w-6xl overflow-hidden px-5 pb-20 pt-28 text-white md:px-10">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(253,224,71,0.06),transparent_55%)]" />

      <div className="relative z-10 grid gap-3">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-yellow-300">
          Tbilisi Style 21
        </p>
        <h1 className="font-heading text-4xl font-black uppercase tracking-[0.04em] md:text-6xl">
          {title}
        </h1>
        <span className="mt-1 block h-[3px] w-16 rounded-full bg-yellow-300" />
      </div>

      <div className="relative z-10">
        <TicketsClient tickets={tickets} />
      </div>
    </main>
  );
}
