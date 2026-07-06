import DOMPurify from "isomorphic-dompurify";

/**
 * Tags/attributes the Filament rich-text editor can produce. Anything outside
 * this allowlist is stripped so admin- or legacy-import-authored HTML can't
 * inject scripts, event handlers, or unsafe URLs when rendered via
 * dangerouslySetInnerHTML.
 */
const ALLOWED_TAGS = [
  "p", "br", "strong", "b", "em", "i", "s", "del", "u",
  "h2", "h3", "ul", "ol", "li", "blockquote", "a",
];
const ALLOWED_ATTR = ["href", "rel", "target"];

/** Sanitize admin/CMS rich-text HTML before rendering it into the page. */
export function sanitizeRichHtml(html: string): string {
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOWED_URI_REGEXP: /^(?:https?|mailto|tel):/i,
  });
}
