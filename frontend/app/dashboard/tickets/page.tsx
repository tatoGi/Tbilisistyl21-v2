import { listTickets } from "@/lib/tickets";
import TicketsClient from "./TicketsClient";

export const dynamic = "force-dynamic";

export default async function TicketsPage() {
  const tickets = await listTickets({ publicOnly: true });

  return (
    <main className="mx-auto min-h-screen w-full max-w-6xl px-5 pb-16 pt-28 text-white md:px-10">
      <div className="grid gap-3">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-yellow-300">
          Tbilisi Style 21
        </p>
        <h1 className="font-heading text-4xl font-black uppercase tracking-[0.04em] md:text-6xl">
          Tickets
        </h1>
        <span className="mt-1 block h-[3px] w-16 rounded-full bg-yellow-300" />
      </div>

      <TicketsClient tickets={tickets} />
    </main>
  );
}