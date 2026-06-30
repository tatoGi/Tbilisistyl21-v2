'use client'

import { useState } from "react"
import BuyTicketModal from "./BuyTicketModal"

interface Ticket {
  id: string
  title: string
  priceGel: number
  eventDate?: string
  location?: string
  description?: string
  status: string
  quantity: number
}

export default function TicketsClient({ tickets }: { tickets: Ticket[] }) {
  const [selectedTicket, setSelectedTicket] = useState<{
    id: string
    title: string
    priceGel: number
    eventDate?: string
    location?: string
  } | null>(null)

  return (
    <>
      <div className="mt-10 grid gap-6 md:grid-cols-2">
        {tickets.length ? (
          tickets.map((ticket) => {
            const soldOut = ticket.status === "sold_out" || ticket.quantity <= 0;
            const low = !soldOut && ticket.quantity <= 20;
            return (
              <article
                key={ticket.id}
                className="group relative flex flex-col gap-5 overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.02] p-6 shadow-xl shadow-black/30 transition duration-300 hover:-translate-y-0.5 hover:border-yellow-300/40 hover:shadow-2xl hover:shadow-black/50"
              >
                {/* Soft accent glow */}
                <div className="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-yellow-300/10 blur-3xl transition duration-500 group-hover:bg-yellow-300/20" />

                <div className="relative flex items-start justify-between gap-4">
                  <div className="min-w-0">
                    <h2 className="font-heading text-2xl font-black uppercase leading-tight">
                      {ticket.title}
                    </h2>
                    <p className="mt-2 text-sm text-white/55">
                      {ticket.eventDate || "Date TBA"} ·{" "}
                      {ticket.location || "Location TBA"}
                    </p>
                  </div>
                  <span className="shrink-0 rounded-full bg-yellow-300 px-4 py-1.5 text-sm font-black text-black shadow-lg shadow-yellow-300/20">
                    {ticket.priceGel} ₾
                  </span>
                </div>

                {ticket.description ? (
                  <p className="relative whitespace-pre-line text-sm leading-6 text-white/75">
                    {ticket.description}
                  </p>
                ) : null}

                <div className="relative mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4">
                  <span
                    className={`inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide ${
                      soldOut ? "text-red-300/80" : low ? "text-amber-300" : "text-emerald-300/90"
                    }`}
                  >
                    <span
                      className={`h-1.5 w-1.5 rounded-full ${
                        soldOut ? "bg-red-400" : low ? "bg-amber-400" : "bg-emerald-400"
                      }`}
                    />
                    {soldOut ? "Sold out" : `${ticket.quantity} available`}
                  </span>
                  {!soldOut ? (
                    <button
                      onClick={() => setSelectedTicket({
                        id: ticket.id,
                        title: ticket.title,
                        priceGel: ticket.priceGel,
                        eventDate: ticket.eventDate,
                        location: ticket.location,
                      })}
                      className="rounded-xl bg-yellow-300 px-6 py-3 text-xs font-black uppercase tracking-wide text-black shadow-lg shadow-yellow-300/20 transition hover:bg-white"
                    >
                      Buy Ticket
                    </button>
                  ) : null}
                </div>
              </article>
            );
          })
        ) : (
          <p className="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-8 text-center text-white/55 md:col-span-2">
            Tickets are not available yet.
          </p>
        )}
      </div>

      {/* Modal */}
      {selectedTicket && (
        <BuyTicketModal
          isOpen={!!selectedTicket}
          onClose={() => setSelectedTicket(null)}
          ticket={selectedTicket}
        />
      )}
    </>
  )
}