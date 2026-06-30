import Image from "next/image";
import visaLogo from "@/public/paymentLogo/Visa_Brandmark_White_RGB_2021.png";
import mastercardLogo from "@/public/paymentLogo/ms_hrz_opt_rev_75_3x.png";

/**
 * Visa / Mastercard acceptance marks shown at checkout, so customers (and the
 * acquiring bank) can see which cards the payment is processed with. Uses the
 * official white/reversed brand logos, which read well on the dark modals.
 */
export default function PaymentCardBadges({
  className = "",
}: {
  className?: string;
}) {
  return (
    <div
      className={`flex items-center justify-center gap-5 ${className}`}
      aria-label="We accept Visa and Mastercard"
    >
      <span className="relative h-6 w-12">
        <Image src={visaLogo} alt="Visa" fill className="object-contain" sizes="48px" />
      </span>
      <span className="relative h-7 w-12">
        <Image src={mastercardLogo} alt="Mastercard" fill className="object-contain" sizes="48px" />
      </span>
    </div>
  );
}
