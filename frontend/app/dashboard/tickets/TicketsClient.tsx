'use client'

import { useState } from "react"
import BuyTicketModal from "./BuyTicketModal"
import { sanitizeRichHtml } from "@/lib/sanitize"

function formatEventDate(value?: string) {
  if (!value) return "Date TBA"
  const raw = value.includes("T") ? value.split("T")[0] : value
  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  })
}

interface Ticket {
  id: string
  title: string
  priceGel: number
  imageUrl?: string | null
  eventDate?: string
  location?: string
  description?: string
  status: string
  quantity: number
  category: string
  features: string[]
  isFeatured: boolean
  remaining: number
  percentLeft: number
}

function DescriptionFallback({ content }: { content: string }) {
  const isHtml = /<[a-z][\s\S]*>/i.test(content)
  if (isHtml) {
    return (
      <div
        className="rich-text text-sm leading-relaxed text-[color:var(--ts-body)]"
        dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(content) }}
      />
    )
  }
  return (
    <p className="whitespace-pre-line text-sm leading-relaxed text-[color:var(--ts-body)]">
      {content}
    </p>
  )
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
      <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3 lg:items-stretch">
        {tickets.length ? (
          tickets.map((ticket) => {
            const soldOut = ticket.status === "sold_out" || ticket.remaining <= 0
            const featured = ticket.isFeatured && !soldOut

            return (
              <article
                key={ticket.id}
                className={`relative flex h-full flex-col rounded-[20px] border p-8 transition duration-300 ${
                  featured
                    ? "border-[#e8b84b]/40 bg-[linear-gradient(160deg,rgba(232,184,75,0.14),rgba(255,255,255,0.02))] shadow-[0_20px_60px_rgba(232,184,75,0.12)]"
                    : "border-white/10 bg-[linear-gradient(160deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01))] hover:border-[#e8b84b]/25"
                } ${soldOut ? "opacity-70" : ""}`}
              >
                {featured ? (
                  <div className="absolute right-7 top-[-1px] rounded-b-lg bg-[#e8b84b] px-3.5 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.08em] text-[#1a1206]">
                    POPULAR
                  </div>
                ) : null}

                {/* Category tag */}
                <div
                  className={`mb-6 self-start rounded-full px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] ${
                    featured
                      ? "bg-[#e8b84b] text-[#1a1206]"
                      : "bg-white/[0.06] text-[color:var(--ts-muted)]"
                  }`}
                >
                  {ticket.category || "Festival Pass"}
                </div>

                {/* Title */}
                <h2 className="font-unbounded mb-2.5 text-[22px] font-bold leading-snug text-[color:var(--ts-head)]">
                  {ticket.title}
                </h2>

                {/* Date */}
                <div className="mb-6 flex items-center gap-2">
                  <span className="h-1.5 w-1.5 rounded-full bg-[color:var(--ts-muted)]" />
                  <span className="text-[13px] text-[color:var(--ts-muted)]">
                    {formatEventDate(ticket.eventDate)}
                    {ticket.location ? ` · ${ticket.location}` : ""}
                  </span>
                </div>

                {/* Price */}
                <div className="mb-7 flex items-baseline gap-1.5">
                  <span className="font-unbounded text-[42px] font-extrabold leading-none text-[color:var(--ts-head)]">
                    {ticket.priceGel}
                  </span>
                  <span className="text-base text-[color:var(--ts-muted)]">₾</span>
                </div>

                {/* Features checklist (falls back to the ticket description) */}
                <div className="mb-7 flex flex-1 flex-col gap-3">
                  {ticket.features.length ? (
                    ticket.features.map((f, i) => (
                      <div key={i} className="flex items-start gap-2.5 text-sm leading-relaxed text-[color:var(--ts-body)]">
                        <span className="mt-0.5 text-[#e8b84b]">✓</span>
                        <span>{f}</span>
                      </div>
                    ))
                  ) : ticket.description ? (
                    <DescriptionFallback content={ticket.description} />
                  ) : null}
                </div>

                {/* Availability */}
                <div className="mb-5">
                  <div className="mb-1.5 flex justify-between text-xs text-[color:var(--ts-muted)]">
                    <span>
                      {soldOut ? "ამოიწურა" : `${ticket.remaining} ხელმისაწვდომი`}
                    </span>
                    <span>{ticket.percentLeft}%</span>
                  </div>
                  <div className="h-1 overflow-hidden rounded-full bg-white/[0.08]">
                    <div
                      className="h-full rounded-full bg-[#e8b84b]"
                      style={{ width: `${ticket.percentLeft}%` }}
                    />
                  </div>
                </div>

                {/* CTA */}
                {soldOut ? (
                  <span className="flex w-full cursor-not-allowed items-center justify-center rounded-full bg-white/10 py-4 text-xs font-black uppercase tracking-[0.14em] text-white/40">
                    ამოიწურა
                  </span>
                ) : (
                  <button
                    type="button"
                    onClick={() =>
                      setSelectedTicket({
                        id: ticket.id,
                        title: ticket.title,
                        priceGel: ticket.priceGel,
                        eventDate: ticket.eventDate,
                        location: ticket.location,
                      })
                    }
                    className="group/btn flex w-full items-center justify-center gap-2 rounded-full bg-[#e8b84b] py-4 text-sm font-bold text-[#1a1206] transition duration-300 hover:bg-white"
                  >
                    <span>ბილეთის ყიდვა</span>
                    <span aria-hidden className="transition-transform duration-300 group-hover/btn:translate-x-0.5">
                      →
                    </span>
                  </button>
                )}
              </article>
            )
          })
        ) : (
          <p className="rounded-[20px] border border-dashed border-white/15 bg-white/[0.02] p-10 text-center text-white/50 md:col-span-2 lg:col-span-3">
            Tickets are not available yet.
          </p>
        )}
      </div>

      {selectedTicket ? (
        <BuyTicketModal
          isOpen={!!selectedTicket}
          onClose={() => setSelectedTicket(null)}
          ticket={selectedTicket}
        />
      ) : null}
    </>
  )
}
