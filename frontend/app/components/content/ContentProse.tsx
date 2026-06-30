import type { ReactNode } from "react";

/** Readable body copy for festival content pages. */
export default function ContentProse({ children }: { children: ReactNode }) {
  return <div className="ts-content-prose">{children}</div>;
}
