import Link from 'next/link'
import { getTranslations } from 'next-intl/server'

type SuccessPageProps = {
  searchParams: Promise<{
    type?: string
    orderId?: string
    ticketId?: string
  }>
}

export default async function SuccessPage({ searchParams }: SuccessPageProps) {
  const [params, t] = await Promise.all([searchParams, getTranslations('checkoutResult')])
  const isProduct = params.type === 'product'

  const message = isProduct ? t('productSuccessMessage') : t('ticketSuccessMessage')

  const backHref = isProduct ? '/dashboard/shop' : '/dashboard/tickets'
  const backLabel = isProduct ? t('backToShop') : t('backToHome')

  return (
    <div className="min-h-screen bg-[#0b0906] text-[color:var(--ts-body)] flex items-center justify-center px-4 relative overflow-hidden">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(232,184,75,0.18),transparent_45%)]" />

      <div className="mt-[100px] relative z-10 w-full max-w-lg border border-white/10 bg-white/[0.03] backdrop-blur-2xl rounded-[24px] p-10 shadow-[0_20px_60px_rgba(232,184,75,0.12)] text-center">
        <div className="w-24 h-24 rounded-full border border-[#e8b84b]/40 bg-[#e8b84b]/10 flex items-center justify-center mx-auto mb-8">
          <span className="text-5xl text-[#e8b84b]">✓</span>
        </div>

        <p className="font-unbounded uppercase tracking-[0.22em] text-[#e8b84b] text-[13px] mb-4 font-bold">
          TBILISI STYLE 21
        </p>

        <h1 className="font-unbounded text-[clamp(2rem,5vw,2.75rem)] font-extrabold mb-4 leading-tight text-[color:var(--ts-head)]">
          {t('paymentSuccessTitle')}
        </h1>

        <p className="text-[color:var(--ts-body)] text-base mb-10 leading-8">
          {message}
        </p>

        <Link
          href={backHref}
          className="w-full inline-flex items-center justify-center bg-[#e8b84b] text-[#1a1206] font-bold py-4 rounded-full hover:bg-white transition duration-300"
        >
          {backLabel}
        </Link>
      </div>
    </div>
  )
}
