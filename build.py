# -*- coding: utf-8 -*-
"""Static generator for the Tbilisi Style 21 redesign preview.

Shared partials (head / nav / footer bar) and the design tokens live once here
and in assets/style.css. Each page is assembled into a plain static .html file
at the repo root so GitHub Pages can serve it directly — no build step or
runtime is needed on the client's side. Re-run `python build.py` after edits.
"""
import os

ROOT = os.path.dirname(os.path.abspath(__file__))

FONTS = (
    '<link rel="preconnect" href="https://fonts.googleapis.com">'
    '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
    '<link href="https://fonts.googleapis.com/css2?'
    'family=Noto+Sans+Georgian:wght@400;500;600;700;800&'
    'family=Unbounded:wght@500;700;800&display=swap" rel="stylesheet">'
)


def nav(active_lang="KA"):
    langs = ["KA", "EN", "RU", "UA"]
    pills = "".join(
        '<div class="lang__item{cls}">{l}</div>'.format(
            l=l, cls=" lang__item--active" if l == active_lang else ""
        )
        for l in langs
    )
    return (
        '<div class="nav">'
        '<a class="brand" href="index.html">'
        '<div class="brand__mark">21</div>'
        '<div class="brand__word">TBILISI STYLE</div>'
        '</a>'
        '<div class="nav__right">'
        '<div class="lang">{pills}</div>'
        '<div class="nav__social"><div>◎</div><div>♪</div></div>'
        '<div class="hamburger"><span></span><span></span></div>'
        '</div>'
        '</div>'
    ).format(pills=pills)


def footerbar():
    return (
        '<div class="footerbar">'
        '<div class="player">'
        '<div class="player__btn">▶</div>'
        '<div><div class="player__title">New Era in Tbilisi</div>'
        '<div class="player__sub">Tbilisi Style 21</div></div>'
        '<div class="player__bar"><div class="player__fill"></div></div>'
        '</div>'
        '<a class="cta-pill" href="tickets.html">ბილეთების ყიდვა <span>↗</span></a>'
        '</div>'
        '<div class="foot-spacer"></div>'
    )


def eyebrow(text, pink=False):
    return (
        '<div class="eyebrow{mod}"><div class="eyebrow__line"></div>'
        '<div class="eyebrow__text">{t}</div></div>'
    ).format(t=text, mod=" eyebrow--pink" if pink else "")


def ph(tag="", extra="", cls=""):
    label = '<span class="ph__tag">{}</span>'.format(tag) if tag else ""
    return '<div class="ph {cls}" style="{extra}">{label}</div>'.format(
        cls=cls, extra=extra, label=label
    )


def document(title, body, nav_active="KA", show_nav=True, show_footer=True,
             ambient=True, pink=False):
    parts = ['<!DOCTYPE html><html lang="ka"><head>',
             '<meta charset="utf-8">',
             '<meta name="viewport" content="width=device-width, initial-scale=1">',
             '<title>{}</title>'.format(title),
             FONTS,
             '<link rel="stylesheet" href="assets/style.css">',
             '</head><body><div class="page">']
    if ambient:
        parts.append('<div class="ambient{}"></div>'.format(" ambient--pink" if pink else ""))
    if show_nav:
        parts.append(nav(nav_active))
    parts.append(body)
    if show_footer:
        parts.append(footerbar())
    parts.append('</div></body></html>')
    return "".join(parts)


# --------------------------------------------------------------------- PAGES

def home_page():
    body = (
        '<div class="splash">'
        + ph("hero / splash", cls="")
        + '<div class="splash__scrim"></div>'
        '<div class="splash__top">'
        '<h1 class="splash__title">ENTER THE ENERGY</h1>'
        '<p class="splash__sub">TBILISI STYLE 21</p>'
        '</div>'
        '<div class="splash__cta">'
        '<a class="btn btn--gold pulse" href="festival.html">'
        'დაიწყე ენერგია <span>→</span></a>'
        '</div>'
        '</div>'
    )
    return document("Tbilisi Style 21 — Enter the Energy", body,
                    show_nav=False, show_footer=False, ambient=False)


def festival_page():
    tiles = [
        ("მთავარი სცენა", "ღამის გულისცემა", "show.html"),
        ("ქვევრის სცენა", "ქართული რიტმი", "show.html"),
        ("ტექნო ქვევრი", "ელექტრონული ღამე", "show.html"),
        ("Food Zone", "გემოვანი და ბარები", "show.html"),
        ("Line Up", "არტისტები", "show.html"),
        ("Joker Zone", "VIP გამოცდილება", "show.html"),
    ]
    cards = "".join(
        '<a class="gallery-card" href="{href}">'
        '<div class="gallery-card__thumb ph">{n}</div>'
        '<div class="gallery-card__body">'
        '<div class="gallery-card__title">{t}</div>'
        '<div class="gallery-card__desc">{d}</div></div></a>'.format(
            href=h, n=i + 1, t=t, d=d)
        for i, (t, d, h) in enumerate(tiles)
    )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">ფესტივალი</h1>'
        '<p class="hero__sub">ექვსი დღე, ოთხი სცენა, ათასეული არტისტი — ერთი ქალაქი, რომელიც არასოდეს დაგიძინებს.</p>'
        '</div>'
        '<div class="gallery">' + cards + '</div>'
    )
    return document("Tbilisi Style 21 — ფესტივალი", body, nav_active="KA")


def show_page():
    stats = [
        ("60×15მ", "სცენის ზომა"),
        ("6", "დღე"),
        ("5000+", "ვიზიტორი"),
        ("2026", "ნოემბერი"),
    ]
    stat_html = "".join(
        '<div class="stat"><div class="stat__value">{v}</div>'
        '<div class="stat__label">{l}</div></div>'.format(v=v, l=l)
        for v, l in stats
    )
    info_rows = [
        ("თარიღი", "30 ნოემბერი, 2026"),
        ("ლოკაცია", "Tbilisi, Georgia"),
        ("ხანგრძლივობა", "6 დღე"),
    ]
    rows_html = "".join(
        '<div class="info-row"><div class="info-row__k">{k}</div>'
        '<div class="info-row__v">{v}</div></div>'.format(k=k, v=v)
        for k, v in info_rows
    )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">მთავარი სცენა</h1>'
        '<div class="stats">' + stat_html + '</div>'
        '</div>'
        '<div class="content-grid">'
        '<div>'
        '<p class="lead">Tbilisi Style-ის მთავარი სცენა შექმნილია, როგორც ღამის გულისცემა — 60×15 მეტრზე გადაშლილი შუქებისა და ხმის სისტემა, სადაც ევროპული ტურნეების ტექნიკა ხვდება ქართულ სტუმართმოყვარეობას. ექვსი დღის განმავლობაში აქ იცვლება ჟანრები, მაგრამ არ იცვლება განწყობა: ცეცხლი, ორთქლი და შუქები ღამის ბოლომდე.</p>'
        '<div class="tags">'
        '<div class="tag">LED ეკრანი</div>'
        '<div class="tag">ევროპული ხმის ტექნიკა</div>'
        '<div class="tag">პიროტექნიკა</div>'
        '</div>'
        '<a class="textlink" href="#">სრული ხედვა გალერეაში <span>→</span></a>'
        '</div>'
        '<div class="info-card">'
        '<div class="info-card__label">ღონისძიება</div>'
        '<div class="info-rows">' + rows_html + '</div>'
        '<div class="divider"></div>'
        '<div class="info-card__note">ბილეთები შემოიფარგლება ადგილების რაოდენობით. ადრეული ჯავშანი გირანტირებთ საუკეთესო ხედვას სცენაზე.</div>'
        '</div>'
        '</div>'
        '<div class="hero-image">'
        '<div class="hero-image__frame">'
        + ph("main stage", "height:640px")
        + '<div class="hero-image__scrim"></div>'
        '<div class="hero-image__cap">'
        '<div class="hero-image__cap-title">WELCOME TO TBILISI STYLE</div>'
        '<div class="hero-image__cap-sub">მთავარი სცენა · ღამის კადრი</div>'
        '</div>'
        '</div>'
        '</div>'
        '<div class="split">'
        '<div class="split__media">' + ph("joker zone", "height:460px", cls="ph--pink") + '</div>'
        '<div>'
        + eyebrow("JOKER ZONE", pink=True)
        + '<h2>ღამის ყველაზე ექსკლუზიური სივრცე</h2>'
        '<p>VIP ბილეთით 5000 ჯოკერ-ადამიანს შორის მხოლოდ რჩეულები ხვდებიან იქ, სადაც ფესტივალის ენერგია ხდება პირადი გამოცდილება — ცალკე ბარი, ცოცხალი დიჯეი და ქალაქზე გადამხედი ტერასა.</p>'
        '<a class="textlink textlink--pink" href="tickets.html">VIP ბილეთის დეტალები <span>→</span></a>'
        '</div>'
        '</div>'
    )
    return document("Tbilisi Style 21 — მთავარი სცენა", body, nav_active="KA")


def partners_page():
    official = "".join(
        '<div class="logo-tile"><div class="logo-slot">LOGO</div></div>'
        for _ in range(8)
    )
    media = "".join(
        '<div class="logo-tile logo-tile--media"><div class="logo-slot logo-slot--sm">LOGO</div></div>'
        for _ in range(6)
    )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">პარტნიორები</h1>'
        '<p class="hero__sub">ბრენდები და მედია, რომლებიც გვერდში გვედგნენ და ერთად ვქმნით ტბილისის ყველაზე დიდ ღამის ფესტივალს.</p>'
        '</div>'
        '<div class="section">'
        '<div class="section-label"><div class="section-label__text">მთავარი პარტნიორი</div><div class="section-label__rule"></div></div>'
        '<div class="logo-card--title"><div class="logo-slot--title logo-slot">TITLE PARTNER</div></div>'
        '</div>'
        '<div class="section">'
        '<div class="section-label"><div class="section-label__text">ოფიციალური პარტნიორები</div><div class="section-label__rule"></div></div>'
        '<div class="logo-grid logo-grid--official">' + official + '</div>'
        '</div>'
        '<div class="section">'
        '<div class="section-label"><div class="section-label__text">მედია პარტნიორები</div><div class="section-label__rule"></div></div>'
        '<div class="logo-grid logo-grid--media">' + media + '</div>'
        '</div>'
        '<div class="cta-banner">'
        '<div class="cta-banner__inner">'
        '<div>'
        '<div class="cta-banner__title">გახდი Tbilisi Style-ის პარტნიორი</div>'
        '<div class="cta-banner__text">დაუკავშირდი ჩვენს გუნდს პარტნიორობის შესაძლებლობებზე და მოიპოვე წვდომა 5000+ ვიზიტორზე.</div>'
        '</div>'
        '<a class="btn btn--gold" href="show.html">დაგვიკავშირდი <span>↗</span></a>'
        '</div>'
        '</div>'
    )
    return document("Tbilisi Style 21 — პარტნიორები", body, nav_active="KA")


def tickets_page():
    tickets = [
        dict(tag="STANDARD", gold=False, featured=False,
             title="1-დღიანი სტანდარტული ბილეთი",
             date="1 აგვისტო, 2027", price="422",
             features=[
                 "წვდომა ფესტივალის მთელ ტერიტორიაზე",
                 "მთავარი სცენა",
                 "ღია ბარი და კვების ზონები",
                 "ლაივ მუსიკის ზონა",
             ], available="5000", pct=100),
        dict(tag="JOKER PASS", gold=True, featured=True,
             title="3-დღიანი ჯოკერ ბილეთი",
             date="27 აგვისტო, 2027", price="587",
             features=[
                 "ექსკლუზიური VIP შესასვლელი",
                 "პრემიუმ ბარი და კომფორტული დასასვენებელი",
                 "ინდივიდუალური მომსახურება 3 დღე",
                 "ჯოკერ ზონის განსაკუთრებული პრივილეგიები",
                 "ადრეული წვდომა მომავალ ღონისძიებებზე",
             ], available="4983", pct=99),
        dict(tag="FULL PASS", gold=False, featured=False,
             title="6-დღიანი სრული ფესტივალი",
             date="25–30 აგვისტო, 2027", price="1722",
             features=[
                 "წვდომა ყველა დღეს, ყველა სცენაზე",
                 "გარანტირებული ადგილი მთავარ სცენასთან",
                 "სპეციალური merch პაკეტი",
                 "პრიორიტეტული შესასვლელი",
                 "ექსკლუზიური after-party მოწვევა",
             ], available="1200", pct=61),
    ]
    cards = []
    for t in tickets:
        feats = "".join('<div class="feature">{}</div>'.format(f) for f in t["features"])
        cards.append(
            '<div class="ticket-card{fc}">'
            '{ribbon}'
            '<div class="ticket__tag{tg}">{tag}</div>'
            '<div class="ticket__title">{title}</div>'
            '<div class="ticket__date"><span>{date}</span></div>'
            '<div class="ticket__price"><b>{price}</b><i>₾</i></div>'
            '<div class="ticket__features">{feats}</div>'
            '<div class="avail"><div class="avail__head"><div>{avail} ხელმისაწვდომი</div><div>{pct}%</div></div>'
            '<div class="avail__bar"><div class="avail__fill" style="width:{pct}%"></div></div></div>'
            '<a class="btn btn--gold btn--block" href="#">ბილეთის ყიდვა <span>→</span></a>'
            '</div>'.format(
                fc=" ticket-card--featured" if t["featured"] else "",
                ribbon='<div class="ribbon">POPULAR</div>' if t["featured"] else "",
                tg=" ticket__tag--gold" if t["gold"] else "",
                tag=t["tag"], title=t["title"], date=t["date"], price=t["price"],
                feats=feats, avail=t["available"], pct=t["pct"],
            )
        )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">ბილეთები</h1>'
        '<p class="hero__sub">აირჩიე ფორმატი, რომელიც შენ გერგება — ერთი ღამიდან სრულ ფესტივალამდე.</p>'
        '</div>'
        '<div class="ticket-grid">' + "".join(cards) + '</div>'
    )
    return document("Tbilisi Style 21 — ბილეთები", body, nav_active="KA")


def shop_page():
    products = [
        ("Festival Hoodie", "უნისექს — S/M/L/XL", "180"),
        ("Tour T-Shirt 21", "100% ბამბა", "75"),
        ("Bucket Hat", "ერთი ზომა", "60"),
        ("Tote Bag", "კანვასი", "45"),
        ("Enamel Pin Set", "3 ცალი", "35"),
        ("Vinyl — New Era", "LP", "120"),
    ]
    cards = "".join(
        '<div class="product-card">'
        '<div class="product-card__media">' + ph("product") + '</div>'
        '<div class="product-card__body">'
        '<div class="product-card__title">{t}</div>'
        '<div class="product-card__meta">{m}</div>'
        '<div class="product-card__foot">'
        '<div class="price">{p}<i>₾</i></div>'
        '<a class="btn btn--ghost" href="#">კალათაში</a>'
        '</div></div></div>'.format(t=t, m=m, p=p)
        for t, m, p in products
    )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">მაღაზია</h1>'
        '<p class="hero__sub">ოფიციალური merch — წაიღე ფესტივალის ნაწილი სახლში.</p>'
        '</div>'
        '<div class="grid-3">' + cards + '</div>'
    )
    return document("Tbilisi Style 21 — მაღაზია", body, nav_active="KA")


def news_page():
    feature = (
        '<a class="news-card news-card--feature" href="news-article.html">'
        '<div class="news-card__media">' + ph("featured") + '</div>'
        '<div class="news-card__body">'
        '<div class="news-card__date">15 ივნისი, 2026</div>'
        '<h3 class="news-card__title">Line Up 2026 — პირველი არტისტები გამოცხადდა</h3>'
        '<div class="news-card__excerpt">ფესტივალი ამზადებს წლის ყველაზე დატვირთულ შემადგენლობას — ასეთი ენერგია ტბილისს ჯერ არ უნახავს.</div>'
        '<div class="textlink" style="margin-top:8px">ვრცელად <span>→</span></div>'
        '</div></a>'
    )
    items = [
        ("10 ივნისი", "ახალი სცენა: Techno Qvevri"),
        ("2 ივნისი", "Food Zone — რას შემოგთავაზებთ"),
        ("28 მაისი", "ბილეთების გაყიდვა დაიწყო"),
    ]
    cards = feature + "".join(
        '<a class="news-card" href="news-article.html">'
        '<div class="news-card__media">' + ph("news") + '</div>'
        '<div class="news-card__body">'
        '<div class="news-card__date">{d}, 2026</div>'
        '<h3 class="news-card__title">{t}</h3>'
        '<div class="news-card__excerpt">მოკლე ანოტაცია სიახლის შესახებ — დეტალები სტატიაში.</div>'
        '</div></a>'.format(d=d, t=t)
        for d, t in items
    )
    body = (
        '<div class="hero">'
        + eyebrow("TBILISI STYLE 21")
        + '<h1 class="hero__title">სიახლეები</h1>'
        '<p class="hero__sub">უახლესი ჩანაწერები, განცხადებები და კადრები ფესტივალიდან.</p>'
        '</div>'
        '<div class="news-grid">' + cards + '</div>'
    )
    return document("Tbilisi Style 21 — სიახლეები", body, nav_active="KA")


def article_page():
    body = (
        '<div class="hero" style="padding-bottom:24px">'
        + eyebrow("სიახლე · 15 ივნისი, 2026")
        + '<h1 class="hero__title" style="font-size:clamp(30px,5vw,54px)">Line Up 2026 — პირველი არტისტები გამოცხადდა</h1>'
        '</div>'
        '<div class="article">'
        '<div class="article__media">' + ph("article hero") + '</div>'
        '<div class="prose">'
        '<p>Tbilisi Style 21 ამზადებს წლის ყველაზე დატვირთულ შემადგენლობას. ექვსი დღის განმავლობაში სცენაზე გავა არაერთი გახმაურებული სახელი.</p>'
        '<h2>რას ველოდებით</h2>'
        '<p>სამი დღე მთავარ სცენაზე, დამატებითი აქტივობები ქვევრისა და ტექნო ზონებში. სრული პლეილისტები მალე გამოქვეყნდება.</p>'
        '<p>ბილეთები ხელმისაწვდომია ბილეთების გვერდზე.</p>'
        '</div>'
        '<div style="margin-top:36px"><a class="btn btn--ghost" href="news.html">← ყველა სიახლე</a></div>'
        '</div>'
    )
    return document("Tbilisi Style 21 — Line Up 2026", body, nav_active="KA")


def result_page(ok):
    if ok:
        icon = '<div class="result__icon result__icon--ok">✓</div>'
        title = "გადახდა წარმატებით დასრულდა"
        text = "ბილეთი გაეგზავნა თქვენს ელ.ფოსტაზე. გელოდებით Tbilisi Style 21-ზე!"
        actions = ('<a class="btn btn--gold" href="tickets.html">ბილეთები</a>'
                   '<a class="btn btn--ghost" href="index.html">მთავარი</a>')
    else:
        icon = '<div class="result__icon result__icon--fail">✕</div>'
        title = "გადახდა ვერ განხორციელდა"
        text = "სამწუხაროდ, გადახდა ვერ დასრულდა. სცადე ხელახლა ან დაგვიკავშირდი."
        actions = ('<a class="btn btn--gold" href="tickets.html">ხელახლა ცდა</a>'
                   '<a class="btn btn--ghost" href="index.html">მთავარი</a>')
    body = (
        '<div class="result">' + icon
        + '<h1>' + title + '</h1><p>' + text + '</p>'
        '<div class="result__actions">' + actions + '</div></div>'
    )
    return document("Tbilisi Style 21 — " + ("წარმატება" if ok else "შეცდომა"),
                    body, nav_active="KA", show_footer=False, pink=not ok)


def index_page():
    pages = [
        ("show.html", "Show / Content", "უნივერსალური CMS თარგმანი (~12 გვერდი)"),
        ("partners.html", "Partners", "პარტნიორები — Title / Official / Media"),
        ("tickets.html", "Tickets", "ბილეთები — ტარიფები + POPULAR"),
        ("shop.html", "Shop", "merch — პროდუქტების ბადე"),
        ("news.html", "News", "სიახლეების სია"),
        ("news-article.html", "Article", "ცალკე სტატია"),
        ("success.html", "Success", "გადახდა — წარმატება"),
        ("fail.html", "Fail", "გადახდა — შეცდომა"),
    ]
    cards = "".join(
        '<a class="gallery-card" href="{h}">'
        '<div class="gallery-card__thumb ph">{n}</div>'
        '<div class="gallery-card__body">'
        '<div class="gallery-card__title">{t}</div>'
        '<div class="gallery-card__desc">{d}</div></div></a>'.format(
            h=h, n=i + 1, t=t, d=d)
        for i, (h, t, d) in enumerate(pages)
    )
    body = (
        '<div class="preview-banner">PREVIEW — დამკვეთისთვის, არა-საბოლოო — placeholder კონტენტი</div>'
        '<div class="gallery-head">'
        + eyebrow("TBILISI STYLE 21 · REDESIGN")
        + '<h1 class="hero__title" style="margin-bottom:14px">დიზაინის პრევუ</h1>'
        '<p class="hero__sub">ახალი დიზაინის ყველა გვერდი. დააჭირე ბარათზე სანახავად.</p>'
        '</div>'
        '<div class="gallery">' + cards + '</div>'
    )
    return document("Tbilisi Style 21 — Redesign Preview", body,
                    show_nav=False, show_footer=False)


PAGES = {
    "index.html": index_page,
    "show.html": show_page,
    "partners.html": partners_page,
    "tickets.html": tickets_page,
    "shop.html": shop_page,
    "news.html": news_page,
    "news-article.html": article_page,
    "success.html": lambda: result_page(True),
    "fail.html": lambda: result_page(False),
}


def main():
    for name, fn in PAGES.items():
        with open(os.path.join(ROOT, name), "w", encoding="utf-8") as f:
            f.write(fn())
        print("wrote", name)


if __name__ == "__main__":
    main()
