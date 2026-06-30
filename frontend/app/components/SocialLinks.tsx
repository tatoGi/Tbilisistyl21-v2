import type { SocialLinks } from "@/lib/nav";

type Props = {
  social: SocialLinks;
  /** Optional heading shown above the icons (e.g. "Follow us"). */
  label?: string;
  /** Extra classes for the wrapper. */
  className?: string;
};

/** Instagram / TikTok icon row — renders nothing when no link is set. */
export default function SocialLinksRow({ social, label, className = "" }: Props) {
  if (!social.instagram && !social.tiktok) return null;

  return (
    <div className={`flex flex-col gap-3 ${className}`}>
      {label ? (
        <p className="text-[11px] font-bold uppercase tracking-[0.28em] text-yellow-300/80">
          {label}
        </p>
      ) : null}
      <div className="flex items-center gap-4">
        {social.instagram ? (
          <a
            href={social.instagram}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Instagram"
            className="text-white/70 transition-colors hover:text-yellow-300"
          >
            <InstagramIcon />
          </a>
        ) : null}
        {social.tiktok ? (
          <a
            href={social.tiktok}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="TikTok"
            className="text-white/70 transition-colors hover:text-yellow-300"
          >
            <TikTokIcon />
          </a>
        ) : null}
      </div>
    </div>
  );
}

function InstagramIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
      <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
      <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
    </svg>
  );
}

function TikTokIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M16.6 5.82a4.28 4.28 0 0 1-1.05-2.82h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.27 0 .53.04.78.12v-3.2a5.78 5.78 0 0 0-.78-.05 5.7 5.7 0 1 0 5.7 5.7V9.3a7.34 7.34 0 0 0 4.3 1.38V7.55a4.28 4.28 0 0 1-3.25-1.73z" />
    </svg>
  );
}
