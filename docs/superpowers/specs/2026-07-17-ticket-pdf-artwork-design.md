# Ticket email PDF artwork — design

Date: 2026-07-17 · Status: approved (in-chat)

## Goal

The ticket PDF emailed to buyers gets the festival design (black + orange frame,
"WELCOME TO TBILISI STYLE 21" header, artwork image, EVENT TICKET band, details
left / QR right, entrance footer). Joker tickets embed a different artwork image
in the same layout. Both images are uploaded from the Filament admin.

## Decisions

- **Artwork placement (user-chosen):** joker artwork replaces the standard
  artwork inside the same ticket layout — no second page, no separate attachment.
- **Storage:** two uploads in the existing Site Settings page under a new
  "Ticket email PDF" section, saved as `ticketPdf.artwork` and
  `ticketPdf.jokerArtwork` in the `site_settings` key-value table (no migration).
- **Joker detection:** `event_name` contains "joker" (case-insensitive) — the
  existing rule from `ProcessPaymentCallbackAction`, extracted to
  `SoldTicket::isJokerEvent()` so it is written once.
- **Rendering:** dompdf template rebuilt with table layout (no flexbox), artwork
  embedded from the local public disk path, QR stays a base64 data URI.
  Missing/unset artwork degrades gracefully (ticket renders without the image).
- **Rejected alternative:** per-Ticket artwork field — more flexibility than
  needed now (YAGNI); revisit if events ever need individual designs.

## Touched files

- `backend/app/Filament/Pages/SiteSettings.php` — new section + save key
- `backend/resources/views/pdf/ticket.blade.php` — redesigned template
- `backend/app/Jobs/SendTicketEmailJob.php` — artwork resolution
- `backend/app/Models/SoldTicket.php` — `isJokerEvent()`
- `backend/app/Actions/ProcessPaymentCallbackAction.php` — reuse the helper
- tests: job artwork selection (normal vs joker), PDF generation smoke
