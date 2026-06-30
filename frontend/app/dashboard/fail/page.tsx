import Link from 'next/link'

export default function FailedPage() {
  return (
    <div className="min-h-screen bg-black text-white flex items-center justify-center px-4 relative overflow-hidden">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,0,85,0.18),transparent_40%)]" />

      <div className="mt-[100px] relative z-10 w-full max-w-lg border border-white/10 bg-white/5 backdrop-blur-2xl rounded-[32px] p-10 shadow-[0_0_60px_rgba(255,0,85,0.15)] text-center">
        <div className="w-28 h-28 rounded-full border border-rose-500/30 bg-rose-500/10 flex items-center justify-center mx-auto mb-8 shadow-[0_0_40px_rgba(244,63,94,0.35)]">
          <span className="text-6xl text-rose-500">✕</span>
        </div>

        <p className="uppercase tracking-[6px] text-rose-500 text-sm mb-4 font-semibold">
          TBILISI STYLE
        </p>

        <h1 className="text-4xl md:text-5xl font-black mb-4 leading-tight">
          PAYMENT
          <br />
          FAILED
        </h1>

        <p className="text-gray-400 text-lg mb-10 leading-8">
          Something went wrong while processing your payment.
        </p>

        <Link
          href="/dashboard/tickets"
          className="w-full inline-flex items-center justify-center bg-rose-500 text-white font-bold py-4 rounded-2xl hover:scale-[1.02] transition duration-300 shadow-[0_0_30px_rgba(244,63,94,0.35)]"
        >
          TRY AGAIN
        </Link>
      </div>
    </div>
  )
}