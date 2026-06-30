// The shared site chrome (menu + footer + ticket CTA) now lives in the root
// (frontend) layout via SiteChrome, so it wraps dashboard routes AND CMS
// `[slug]` pages alike. This layout is just a passthrough.
export default function FestivalLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <>{children}</>;
}
