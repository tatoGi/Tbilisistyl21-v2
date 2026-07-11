import Link from 'next/link'

export default function FailedPage() {
  return (
    <div className="min-h-screen bg-[#0b0906] text-[color:var(--ts-body)] flex items-center justify-center px-4 relative overflow-hidden">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,63,164,0.16),transparent_45%)]" />

      <div className="mt-[100px] relative z-10 w-full max-w-lg border border-white/10 bg-white/[0.03] backdrop-blur-2xl rounded-[24px] p-10 shadow-[0_20px_60px_rgba(255,63,164,0.12)] text-center">
        <div className="w-24 h-24 rounded-full border border-[#ff3fa4]/40 bg-[#ff3fa4]/10 flex items-center justify-center mx-auto mb-8">
          <span className="text-5xl text-[#ff3fa4]">✕</span>
        </div>

        <p className="font-unbounded uppercase tracking-[0.22em] text-[#ff3fa4] text-[13px] mb-4 font-bold">
          TBILISI STYLE 21
        </p>

        <h1 className="font-unbounded text-[clamp(2rem,5vw,2.75rem)] font-extrabold mb-4 leading-tight text-[color:var(--ts-head)]">
          გადახდა ვერ განხორციელდა
        </h1>

        <p className="text-[color:var(--ts-body)] text-base mb-10 leading-8">
          სამწუხაროდ, გადახდის დამუშავებისას რაღაც ვერ მოხერხდა. სცადე ხელახლა.
        </p>

        <Link
          href="/dashboard/tickets"
          className="w-full inline-flex items-center justify-center bg-[#ff3fa4] text-[#1a0512] font-bold py-4 rounded-full hover:brightness-110 transition duration-300"
        >
          ხელახლა ცდა
        </Link>
      </div>
    </div>
  )
}