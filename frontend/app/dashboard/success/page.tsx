import Link from 'next/link'

type SuccessPageProps = {
  searchParams: Promise<{
    type?: string
    orderId?: string
    ticketId?: string
  }>
}

export default async function SuccessPage({ searchParams }: SuccessPageProps) {
  const params = await searchParams
  const isProduct = params.type === 'product'

  const message = isProduct
    ? 'Your order has been completed successfully. The pickup QR code has been sent to your email.'
    : 'Your ticket purchase has been completed successfully.'

  const backHref = isProduct ? '/dashboard/shop' : '/dashboard/tickets'
  const backLabel = isProduct ? 'BACK TO SHOP' : 'BACK TO HOME'

  return (
    <div className="min-h-screen bg-black text-white flex items-center justify-center px-4 relative overflow-hidden">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(0,255,170,0.15),transparent_40%)]" />

      <div className="mt-[100px] relative z-10 w-full max-w-lg border border-white/10 bg-white/5 backdrop-blur-2xl rounded-[32px] p-10 shadow-[0_0_60px_rgba(0,255,170,0.12)] text-center">
        <div className="w-28 h-28 rounded-full border border-emerald-400/30 bg-emerald-400/10 flex items-center justify-center mx-auto mb-8 shadow-[0_0_40px_rgba(16,185,129,0.35)]">
          <span className="text-6xl text-emerald-400">✓</span>
        </div>

        <p className="uppercase tracking-[6px] text-emerald-400 text-sm mb-4 font-semibold">
          TBILISI STYLE
        </p>

        <h1 className="text-4xl md:text-5xl font-black mb-4 leading-tight">
          PAYMENT
          <br />
          SUCCESSFUL
        </h1>

        <p className="text-gray-400 text-lg mb-10 leading-8">
          {message}
        </p>

        <Link
          href={backHref}
          className="w-full inline-flex items-center justify-center bg-emerald-400 text-black font-bold py-4 rounded-2xl hover:scale-[1.02] transition duration-300 shadow-[0_0_30px_rgba(16,185,129,0.35)]"
        >
          {backLabel}
        </Link>
      </div>
    </div>
  )
}
