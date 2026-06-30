import Image from "next/image";
import { getTranslations } from "next-intl/server";
import visaLogo from "@/public/paymentLogo/Visa_Brandmark_White_RGB_2021.png";
import mastercardLogo from "@/public/paymentLogo/ms_hrz_opt_rev_75_3x.png";
import { getSiteContact } from "@/lib/nav";

/**
 * Styled contact section for the CMS "Contact" block — phone/email/address come
 * from Site settings (Contact details), so editors never touch code.
 */
export default async function ContactBlock({
  showPayments = true,
}: {
  showPayments?: boolean;
}) {
  const t = await getTranslations("contactUs");
  const contact = await getSiteContact();

  return (
    <div className="mx-auto w-full max-w-4xl">
      <div className="grid gap-5 sm:grid-cols-2">
        <a
          href={`tel:${contact.phoneHref}`}
          className="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-6 transition duration-300 hover:border-yellow-300/40 hover:bg-yellow-300/[0.06] sm:p-8"
        >
          <span className="font-heading text-[10px] font-bold uppercase tracking-[0.28em] text-yellow-300/80">
            {t("phone")}
          </span>
          <span className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white transition group-hover:text-yellow-300 sm:text-3xl">
            {contact.phone}
          </span>
          <span className="text-xs font-semibold uppercase tracking-[0.14em] text-white/45 transition group-hover:text-white/70">
            {t("callCta")} →
          </span>
        </a>

        <a
          href={`mailto:${contact.email}`}
          className="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-6 transition duration-300 hover:border-yellow-300/40 hover:bg-yellow-300/[0.06] sm:p-8"
        >
          <span className="font-heading text-[10px] font-bold uppercase tracking-[0.28em] text-yellow-300/80">
            {t("email")}
          </span>
          <span className="break-all font-heading text-lg font-extrabold uppercase tracking-wide text-white transition group-hover:text-yellow-300 sm:text-xl">
            {contact.email}
          </span>
          <span className="text-xs font-semibold uppercase tracking-[0.14em] text-white/45 transition group-hover:text-white/70">
            {t("emailCta")} →
          </span>
        </a>
      </div>

      {contact.address ? (
        <div className="mt-5 flex flex-col gap-2 rounded-2xl border border-white/10 bg-white/[0.04] p-6 text-center sm:p-8">
          <span className="font-heading text-[10px] font-bold uppercase tracking-[0.28em] text-yellow-300/80">
            {t("address")}
          </span>
          <span className="font-heading text-lg font-extrabold uppercase tracking-wide text-white sm:text-xl">
            {contact.address}
          </span>
        </div>
      ) : null}

      {showPayments ? (
        <section className="mt-8 rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.05] to-transparent p-6 sm:p-8">
          <h2 className="font-heading text-center text-xl font-extrabold uppercase tracking-[0.12em] text-white sm:text-2xl">
            {t("payments")}
          </h2>
          <p className="mt-3 text-center text-sm text-white/55">{t("paymentsNote")}</p>
          <div className="mt-8 flex flex-wrap items-center justify-center gap-10 sm:gap-14">
            <div className="relative h-12 w-28 sm:h-14 sm:w-32">
              <Image src={visaLogo} alt="Visa" fill className="object-contain" />
            </div>
            <div className="relative h-12 w-36 sm:h-14 sm:w-40">
              <Image src={mastercardLogo} alt="Mastercard" fill className="object-contain" />
            </div>
          </div>
        </section>
      ) : null}
    </div>
  );
}
