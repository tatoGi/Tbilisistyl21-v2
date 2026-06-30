'use client'

import { useRef, useState } from 'react'
import PaymentCardBadges from '../../components/PaymentCardBadges'

interface BuyTicketModalProps {
  isOpen: boolean
  onClose: () => void
  ticket: {
    id: string
    title: string
    priceGel: number
    eventDate?: string
    location?: string
  }
}

type LoadingStage = '' | 'checking' | 'reserving' | 'redirecting'

const EMAIL_TYPOS: Record<string, string> = {
  'gmial.com': 'gmail.com',
  'gmal.com': 'gmail.com',
  'gnail.com': 'gmail.com',
  'gmaill.com': 'gmail.com',
  'yhoo.com': 'yahoo.com',
  'yaho.com': 'yahoo.com',
  'hotnail.com': 'hotmail.com',
  'hotmial.com': 'hotmail.com',
  'outlok.com': 'outlook.com',
}

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/

export default function BuyTicketModal({ isOpen, onClose, ticket }: BuyTicketModalProps) {
  const [formData, setFormData] = useState({
    name: '',
    surname: '',
    personalNumber: '',
    email: '',
    terms: false,
  })
  const [hints, setHints] = useState({
    name: '',
    surname: '',
    personalNumber: '',
    email: '',
  })
  const [hintTypes, setHintTypes] = useState({
    name: '' as 'error' | 'success' | '',
    surname: '' as 'error' | 'success' | '',
    personalNumber: '' as 'error' | 'success' | '',
    email: '' as 'error' | 'success' | '',
  })
  const [emailSuggestion, setEmailSuggestion] = useState<string>('')
  const [loadingStage, setLoadingStage] = useState<LoadingStage>('')
  const [error, setError] = useState('')

  const personalNumberRef = useRef<HTMLInputElement>(null)

  if (!isOpen) return null

  // FIX: Latin-only filter WITH visible hint (was: silent filter)
  function handleLatinInput(field: 'name' | 'surname', raw: string) {
    const cleaned = raw.replace(/[^a-zA-Z\s]/g, '')
    setFormData((prev) => ({ ...prev, [field]: cleaned }))
    if (cleaned !== raw) {
      setHints((prev) => ({ ...prev, [field]: 'Latin letters only — as written on your ID' }))
      setHintTypes((prev) => ({ ...prev, [field]: 'error' }))
      setTimeout(() => {
        setHints((prev) => ({ ...prev, [field]: '' }))
        setHintTypes((prev) => ({ ...prev, [field]: '' }))
      }, 2500)
    }
  }

  // FIX: Personal Number — digits only + 11-digit live validation
  function handlePersonalNumber(raw: string) {
    const digits = raw.replace(/\D/g, '').slice(0, 11)
    setFormData((prev) => ({ ...prev, personalNumber: digits }))
    if (digits.length === 11) {
      setHints((prev) => ({ ...prev, personalNumber: '✓ Valid format' }))
      setHintTypes((prev) => ({ ...prev, personalNumber: 'success' }))
    } else if (digits.length > 0) {
      setHints((prev) => ({ ...prev, personalNumber: `${digits.length}/11 digits` }))
      setHintTypes((prev) => ({ ...prev, personalNumber: '' }))
    } else {
      setHints((prev) => ({ ...prev, personalNumber: '' }))
      setHintTypes((prev) => ({ ...prev, personalNumber: '' }))
    }
  }

  // FIX: Email — typo detection + format validation on blur
  function handleEmailBlur() {
    const value = formData.email.trim().toLowerCase()
    if (!value) {
      setHints((prev) => ({ ...prev, email: '' }))
      setHintTypes((prev) => ({ ...prev, email: '' }))
      setEmailSuggestion('')
      return
    }

    const domain = value.split('@')[1]
    if (domain && EMAIL_TYPOS[domain]) {
      const corrected = value.replace(domain, EMAIL_TYPOS[domain])
      setEmailSuggestion(corrected)
      setHints((prev) => ({ ...prev, email: '' }))
      setHintTypes((prev) => ({ ...prev, email: 'error' }))
      return
    }

    setEmailSuggestion('')
    if (EMAIL_REGEX.test(value)) {
      setHints((prev) => ({ ...prev, email: '✓ Looks good' }))
      setHintTypes((prev) => ({ ...prev, email: 'success' }))
    } else {
      setHints((prev) => ({ ...prev, email: '⚠ Please enter a valid email address' }))
      setHintTypes((prev) => ({ ...prev, email: 'error' }))
    }
  }

  function applyEmailSuggestion() {
    setFormData((prev) => ({ ...prev, email: emailSuggestion }))
    setEmailSuggestion('')
    setHints((prev) => ({ ...prev, email: '✓ Looks good' }))
    setHintTypes((prev) => ({ ...prev, email: 'success' }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    // FIX: client-side 11-digit personal number validation before API call
    if (formData.personalNumber.length !== 11) {
      setHints((prev) => ({ ...prev, personalNumber: '⚠ ID must be exactly 11 digits' }))
      setHintTypes((prev) => ({ ...prev, personalNumber: 'error' }))
      personalNumberRef.current?.focus()
      return
    }

    if (!EMAIL_REGEX.test(formData.email)) {
      setError('Please enter a valid email address')
      return
    }

    if (!formData.terms) {
      setError('Please accept the Rules & Terms to continue')
      return
    }

    try {
      // Stage 1: Verify ticket limit
      setLoadingStage('checking')
      const checkRes = await fetch('/api/check-personal-number', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ personalNumber: formData.personalNumber }),
      })
      const checkData = await checkRes.json()

      if (checkData.canPurchase === false) {
        setError('You have already purchased the maximum of 3 tickets with this ID.')
        setLoadingStage('')
        return
      }

      // Stage 2: Create payment order
      setLoadingStage('reserving')
      const orderRes = await fetch('/api/orders/tickets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: formData.name,
          surname: formData.surname,
          personalNumber: formData.personalNumber,
          email: formData.email,
          ticketId: ticket.id,
        }),
      })
      const orderData = await orderRes.json()

      if (orderData.error || !orderData.redirectUrl) {
        // FIX: surface specific error messages (instead of "Server error")
        setError(orderData.error || 'We could not start the payment. Please try again in a moment.')
        setLoadingStage('')
        return
      }

      // Stage 3: Redirect — FIX: same tab (was: window.open _blank → popup blocker)
      setLoadingStage('redirecting')
      window.location.href = orderData.redirectUrl
    } catch (err) {
      setError(
        err instanceof Error && err.message
          ? `Network error: ${err.message}`
          : 'Network error. Please check your connection and try again.',
      )
      setLoadingStage('')
    }
  }

  const isSubmitting = loadingStage !== ''
  const submitLabel: Record<LoadingStage, string> = {
    '': `Pay ${ticket.priceGel} ₾ securely`,
    checking: 'Checking your details…',
    reserving: 'Reserving your ticket…',
    redirecting: 'Redirecting to payment…',
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto"
      onClick={(e) => {
        if (e.target === e.currentTarget && !isSubmitting) onClose()
      }}
    >
      <div className="relative my-8 w-full max-w-md overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-b from-[#111111] to-black shadow-2xl shadow-black/70">
        <button
          type="button"
          onClick={onClose}
          disabled={isSubmitting}
          aria-label="Close"
          className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/5 text-white/60 transition hover:bg-white/10 hover:text-white disabled:opacity-30"
        >
          ✕
        </button>

        <div className="border-b border-white/10 bg-white/[0.02] px-6 pb-5 pt-6">
          <p className="mb-1 text-[10px] font-bold uppercase tracking-[0.2em] text-yellow-300">
            Checkout
          </p>
          <h2 className="text-2xl font-black uppercase leading-tight text-white">{ticket.title}</h2>
        </div>

        <div className="px-6 py-5">
          {/* FIX: Order Summary — transparency before payment */}
          <div className="mb-6 rounded-xl border border-yellow-300/20 bg-yellow-300/[0.04] p-4 text-sm">
            <div className="flex justify-between mb-2 text-white/70">
              <span>Ticket</span>
              <span className="text-white">{ticket.title}</span>
            </div>
            {ticket.eventDate ? (
              <div className="flex justify-between mb-2 text-white/70">
                <span>Date</span>
                <span className="text-white">{ticket.eventDate}</span>
              </div>
            ) : null}
            {ticket.location ? (
              <div className="flex justify-between mb-2 text-white/70">
                <span>Location</span>
                <span className="text-white">{ticket.location}</span>
              </div>
            ) : null}
            <div className="flex justify-between mb-2 text-white/70">
              <span>Quantity</span>
              <span className="text-white">1</span>
            </div>
            <div className="flex justify-between pt-3 mt-3 border-t border-yellow-300/20 font-bold text-base">
              <span className="text-white">Total</span>
              <span className="text-yellow-300">{ticket.priceGel} ₾</span>
            </div>
          </div>

          <form onSubmit={handleSubmit} noValidate className="space-y-4">
            {/* Name */}
            <FormField
              label="Name"
              required
              hint={hints.name || 'Latin letters only — as written on your ID'}
              hintType={hintTypes.name}
            >
              <input
                type="text"
                required
                autoComplete="given-name"
                placeholder="Giorgi"
                disabled={isSubmitting}
                className={inputClass(hintTypes.name)}
                value={formData.name}
                onChange={(e) => handleLatinInput('name', e.target.value)}
              />
            </FormField>

            {/* Surname */}
            <FormField
              label="Surname"
              required
              hint={hints.surname || 'Latin letters only — as written on your ID'}
              hintType={hintTypes.surname}
            >
              <input
                type="text"
                required
                autoComplete="family-name"
                placeholder="Beridze"
                disabled={isSubmitting}
                className={inputClass(hintTypes.surname)}
                value={formData.surname}
                onChange={(e) => handleLatinInput('surname', e.target.value)}
              />
            </FormField>

            {/* Personal Number — FIX: numeric inputMode + 11-digit live check */}
            <FormField
              label="Personal Number (ID)"
              required
              hint={hints.personalNumber || '11 digits — Georgian ID number'}
              hintType={hintTypes.personalNumber}
            >
              <input
                ref={personalNumberRef}
                type="text"
                required
                inputMode="numeric"
                pattern="[0-9]{11}"
                maxLength={11}
                autoComplete="off"
                placeholder="01001012345"
                disabled={isSubmitting}
                className={inputClass(hintTypes.personalNumber)}
                value={formData.personalNumber}
                onChange={(e) => handlePersonalNumber(e.target.value)}
              />
            </FormField>

            {/* Email — FIX: typo detection */}
            <FormField
              label="Email"
              required
              hint={hints.email || 'Your ticket QR will be sent here'}
              hintType={hintTypes.email}
            >
              <input
                type="email"
                required
                inputMode="email"
                autoComplete="email"
                placeholder="you@example.com"
                disabled={isSubmitting}
                className={inputClass(hintTypes.email)}
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                onBlur={handleEmailBlur}
              />
              {emailSuggestion ? (
                <p className="mt-1 text-xs text-yellow-300">
                  Did you mean{' '}
                  <button
                    type="button"
                    onClick={applyEmailSuggestion}
                    className="underline font-semibold"
                  >
                    {emailSuggestion}
                  </button>
                  ?
                </p>
              ) : null}
            </FormField>

            {/* FIX: Terms & Conditions checkbox — was missing (legal risk) */}
            <label className="flex items-start gap-2 text-sm text-white/75 cursor-pointer">
              <input
                type="checkbox"
                required
                disabled={isSubmitting}
                checked={formData.terms}
                onChange={(e) => setFormData({ ...formData, terms: e.target.checked })}
                className="mt-[3px] accent-yellow-300"
              />
              <span>
                I agree to the{' '}
                <a
                  href="/dashboard/rulesAndTerms"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-yellow-300 underline"
                >
                  Rules & Terms
                </a>{' '}
                and confirm my email is correct
              </span>
            </label>

            {error ? (
              <div className="rounded-lg border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-200">
                {error}
              </div>
            ) : null}

            {/* FIX: Multi-stage loading with spinner — was: just "in progress..." */}
            <button
              type="submit"
              disabled={isSubmitting}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-yellow-300 py-4 text-sm font-black uppercase tracking-[0.06em] text-black shadow-lg shadow-yellow-300/20 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-70"
            >
              {isSubmitting ? (
                <>
                  <span
                    aria-hidden
                    className="inline-block h-4 w-4 border-2 border-black/20 border-t-black rounded-full animate-spin"
                  />
                  {submitLabel[loadingStage]}
                </>
              ) : (
                submitLabel['']
              )}
            </button>

            {/* Trust note */}
            <PaymentCardBadges className="pt-1" />
            <p className="text-center text-[11px] text-white/45">
              🔒 Secure 3DS payment by Quipu · No card data stored
            </p>
          </form>
        </div>
      </div>
    </div>
  )
}

function FormField({
  label,
  required,
  hint,
  hintType,
  children,
}: {
  label: string
  required?: boolean
  hint?: string
  hintType: 'error' | 'success' | ''
  children: React.ReactNode
}) {
  return (
    <div>
      <label className="mb-1.5 block text-xs font-semibold uppercase tracking-[0.06em] text-white/90">
        {label} {required ? <span className="text-yellow-300">*</span> : null}
      </label>
      {children}
      {hint ? (
        <p
          className={`mt-1 text-xs ${
            hintType === 'error'
              ? 'text-red-300'
              : hintType === 'success'
                ? 'text-emerald-300'
                : 'text-white/45'
          }`}
        >
          {hint}
        </p>
      ) : null}
    </div>
  )
}

function inputClass(hintType: 'error' | 'success' | '') {
  const base =
    'w-full rounded-lg border bg-white/[0.04] p-3 text-white outline-none transition focus:bg-white/[0.06] disabled:opacity-50'
  if (hintType === 'error') return `${base} border-red-400/60 bg-red-500/[0.04] focus:border-red-400`
  if (hintType === 'success') return `${base} border-emerald-400/60 focus:border-emerald-400`
  return `${base} border-white/15 focus:border-yellow-300`
}
