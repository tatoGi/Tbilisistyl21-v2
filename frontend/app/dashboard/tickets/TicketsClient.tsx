'use client'

import { useState } from "react"
import BuyTicketModal from "./BuyTicketModal"
import { sanitizeRichHtml } from "@/lib/sanitize"

function TicketDescription({ content }: { content: string }) {
  const isHtml = /<[a-z][\s\S]*>/i.test(content)
  if (isHtml) {
    return (
      <div
        className="rich-text relative text-sm leading-relaxed text-white/70"
        dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(content) }}
      />
    )
  }
  return (
    <p className="relative whitespace-pre-line text-sm leading-relaxed text-white/70">
      {content}
    </p>
  )
}

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

function PriceBadge({ price, className = "" }: { price: number; className?: string }) {
  return (
    <span
      className={`inline-flex items-baseline gap-1 rounded-2xl border border-yellow-300/25 bg-black/50 px-4 py-2 shadow-lg shadow-yellow-300/10 backdrop-blur-md ${className}`}
    >
      <span className="font-heading text-xl font-black leading-none text-yellow-300 sm:text-2xl">
        {price}
      </span>
      <span className="text-xs font-bold text-yellow-300/80">₾</span>
    </span>
  )
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
      <div className="mt-12 grid gap-8 md:grid-cols-2 md:items-stretch">
        {tickets.length ? (
          tickets.map((ticket) => {
            const soldOut = ticket.status === "sold_out" || ticket.quantity <= 0
            const low = !soldOut && ticket.quantity <= 20
            const hasImage = Boolean(ticket.imageUrl)

            return (
              <article
                key={ticket.id}
                className={`group relative flex h-full flex-col overflow-hidden rounded-3xl border bg-[#080808] shadow-[0_20px_50px_-20px_rgba(0,0,0,0.8)] transition duration-500 hover:-translate-y-1 hover:shadow-[0_28px_60px_-20px_rgba(253,224,71,0.12)] ${
                  soldOut
                    ? "border-white/5 opacity-90"
                    : "border-white/10 hover:border-yellow-300/35"
                }`}
              >
                {/* Ambient glow */}
                <div className="pointer-events-none absolute -left-24 -top-24 h-56 w-56 rounded-full bg-yellow-300/[0.07] blur-3xl transition duration-700 group-hover:bg-yellow-300/[0.12]" />
                <div className="pointer-events-none absolute -bottom-24 -right-24 h-48 w-48 rounded-full bg-yellow-300/[0.04] blur-3xl" />

                {soldOut ? (
                  <div className="pointer-events-none absolute inset-0 z-20 bg-black/25" />
                ) : null}

                {hasImage ? (
                  <div className="relative w-full overflow-hidden border-b border-white/5 bg-black/40">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={ticket.imageUrl!}
                      alt=""
                      aria-hidden="true"
                      className="absolute inset-0 h-full w-full scale-110 object-cover opacity-35 blur-2xl"
                    />
                    <div className="relative z-10 flex min-h-[12rem] items-center justify-center px-5 py-6 sm:min-h-[14rem]">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img
                        src={ticket.imageUrl!}
                        alt={ticket.title}
                        className="max-h-56 w-full object-contain object-center drop-shadow-[0_12px_32px_rgba(0,0,0,0.65)] transition duration-700 group-hover:scale-[1.03]"
                      />
                    </div>
                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#080808] via-black/20 to-transparent" />
                    <div className="absolute left-5 top-5 z-20">
                      <span className="inline-block rounded-full border border-white/10 bg-black/40 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-white/60 backdrop-blur-sm">
                        Tbilisi Style 21
                      </span>
                    </div>
                    <div className="absolute right-5 top-5 z-20">
                      <PriceBadge price={ticket.priceGel} />
                    </div>
                  </div>
                ) : (
                  <div className="relative overflow-hidden border-b border-white/5 px-6 py-7">
                    <div className="absolute inset-0 bg-gradient-to-br from-yellow-300/[0.14] via-[#0c0c0c] to-black" />
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_0%,rgba(253,224,71,0.18),transparent_50%)]" />
                    <div className="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-yellow-300/40 to-transparent" />
                    <div className="relative flex items-start justify-between gap-4">
                      <span className="inline-block rounded-full border border-yellow-300/20 bg-black/30 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-yellow-300/80">
                        Festival Pass
                      </span>
                      <PriceBadge price={ticket.priceGel} />
                    </div>
                  </div>
                )}

                <div className="relative flex flex-1 flex-col gap-5 p-6 sm:p-7">
                  <div className="relative min-w-0 space-y-3">
                    <h2 className="font-heading text-[clamp(1.35rem,3vw,1.75rem)] font-black uppercase leading-tight tracking-wide text-white transition duration-300 group-hover:text-yellow-300">
                      {ticket.title}
                    </h2>

                    <div className="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
                      <span className="inline-flex items-center gap-2 rounded-xl border border-yellow-300/20 bg-yellow-300/[0.08] px-4 py-2.5 text-sm font-semibold text-white">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2}
                          className="h-4 w-4 shrink-0 text-yellow-300"
                          aria-hidden
                        >
                          <rect x="3" y="4" width="18" height="18" rx="2" />
                          <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        <span className="tracking-wide">{formatEventDate(ticket.eventDate)}</span>
                      </span>
                      <span className="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/[0.06] px-4 py-2.5 text-sm font-semibold text-white/90">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2}
                          className="h-4 w-4 shrink-0 text-yellow-300"
                          aria-hidden
                        >
                          <path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z" />
                          <circle cx="12" cy="10" r="2.5" />
                        </svg>
                        <span className="tracking-wide">{ticket.location || "Location TBA"}</span>
                      </span>
                    </div>
                  </div>

                  {ticket.description ? (
                    <div className="relative rounded-2xl border border-white/5 bg-white/[0.02] px-4 py-4">
                      <TicketDescription content={ticket.description} />
                    </div>
                  ) : null}

                  <div className="relative mt-auto flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-5">
                    <span
                      className={`inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.12em] ${
                        soldOut
                          ? "border-red-400/20 bg-red-400/10 text-red-300"
                          : low
                            ? "border-amber-400/20 bg-amber-400/10 text-amber-300"
                            : "border-emerald-400/20 bg-emerald-400/10 text-emerald-300"
                      }`}
                    >
                      <span
                        className={`h-2 w-2 rounded-full ${
                          soldOut ? "bg-red-400" : low ? "animate-pulse bg-amber-400" : "bg-emerald-400"
                        }`}
                      />
                      {soldOut ? "Sold out" : low ? `Only ${ticket.quantity} left` : `${ticket.quantity} available`}
                    </span>

                    {!soldOut ? (
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
                        className="ts-ticket-pulse group/btn relative inline-flex items-center gap-2 overflow-hidden rounded-full bg-yellow-300 px-7 py-3.5 text-xs font-black uppercase tracking-[0.14em] text-black shadow-[0_8px_30px_-8px_rgba(253,224,71,0.7)] transition duration-300 hover:scale-[1.03] hover:bg-white hover:shadow-[0_12px_36px_-8px_rgba(255,255,255,0.35)]"
                      >
                        <span>Buy Ticket</span>
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth={2.5}
                          className="h-4 w-4 transition duration-300 group-hover/btn:translate-x-0.5"
                          aria-hidden
                        >
                          <path d="M5 12h14" />
                          <path d="m13 6 6 6-6 6" />
                        </svg>
                      </button>
                    ) : (
                      <span className="text-[11px] font-bold uppercase tracking-[0.14em] text-white/30">
                        Unavailable
                      </span>
                    )}
                  </div>
                </div>
              </article>
            )
          })
        ) : (
          <p className="rounded-3xl border border-dashed border-white/15 bg-white/[0.02] p-10 text-center text-white/50 md:col-span-2">
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
