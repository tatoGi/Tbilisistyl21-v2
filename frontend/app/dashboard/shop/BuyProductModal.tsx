"use client";

import { useState } from "react";
import { useTranslations } from "next-intl";
import type { Product } from "@/lib/products";
import PaymentCardBadges from "../../components/PaymentCardBadges";

interface BuyProductModalProps {
  isOpen: boolean;
  onClose: () => void;
  product: Product;
}

type LoadingStage = "" | "reserving" | "redirecting";

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export default function BuyProductModal({
  isOpen,
  onClose,
  product,
}: BuyProductModalProps) {
  const t = useTranslations();
  const availableSizes = product.sizes.filter((s) => s.quantity > 0);

  const [size, setSize] = useState<string>(availableSizes[0]?.size ?? "");
  const [formData, setFormData] = useState({
    name: "",
    surname: "",
    personalNumber: "",
    email: "",
    phone: "",
    terms: false,
  });
  const [loadingStage, setLoadingStage] = useState<LoadingStage>("");
  const [error, setError] = useState("");

  if (!isOpen) return null;

  const isSubmitting = loadingStage !== "";

  const submitLabel: Record<LoadingStage, string> = {
    "": t("buyProduct.payButton", { price: product.priceGel }),
    reserving: t("buyProduct.reserving"),
    redirecting: t("checkout.redirecting"),
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    if (!size) {
      setError(t("buyProduct.selectSizeError"));
      return;
    }
    if (formData.name.trim().length < 2) {
      setError(t("buyProduct.firstNameError"));
      return;
    }
    if (formData.surname.trim().length < 2) {
      setError(t("buyProduct.surnameError"));
      return;
    }
    if (formData.personalNumber.length !== 11) {
      setError(t("buyProduct.personalIdError"));
      return;
    }
    if (!EMAIL_REGEX.test(formData.email)) {
      setError(t("buyProduct.emailError"));
      return;
    }
    if (formData.phone.replace(/\D/g, "").length < 9) {
      setError(t("buyProduct.phoneError"));
      return;
    }
    if (!formData.terms) {
      setError(t("buyProduct.termsError"));
      return;
    }

    try {
      setLoadingStage("reserving");
      const res = await fetch("/api/orders/products", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          productId: product.id,
          size,
          name: formData.name.trim(),
          surname: formData.surname.trim(),
          personalNumber: formData.personalNumber,
          email: formData.email.trim(),
          // The backend accepts digits with an optional leading + only, so
          // strip the spaces/dashes people naturally type ("+995 5XX XX XX XX").
          phone: formData.phone.replace(/[\s\-()]/g, ""),
        }),
      });
      const data = await res.json();

      if (data.error || !data.redirectUrl) {
        // Laravel validation failures (422) carry `message`, not `error`.
        setError(
          data.error ||
            data.message ||
            t("checkout.paymentStartError")
        );
        setLoadingStage("");
        return;
      }

      setLoadingStage("redirecting");
      window.location.href = data.redirectUrl;
    } catch (err) {
      setError(
        err instanceof Error && err.message
          ? `${t("checkout.networkErrorPrefix")}${err.message}`
          : t("checkout.networkErrorGeneric")
      );
      setLoadingStage("");
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-sm"
      onClick={(e) => {
        if (e.target === e.currentTarget && !isSubmitting) onClose();
      }}
    >
      <div className="relative my-8 w-full max-w-md overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-b from-[#111111] to-black shadow-2xl shadow-black/70">
        <button
          type="button"
          onClick={onClose}
          disabled={isSubmitting}
          aria-label={t("a11y.close")}
          className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/5 text-white/60 transition hover:bg-white/10 hover:text-white disabled:opacity-30"
        >
          ✕
        </button>

        <div className="border-b border-white/10 bg-white/[0.02] px-6 pb-5 pt-6">
          <p className="mb-1 text-[10px] font-bold uppercase tracking-[0.2em] text-[#e8b84b]">
            {t("checkout.title")}
          </p>
          <h2 className="text-2xl font-black uppercase text-white">
            {product.title}
          </h2>
        </div>

        <div className="px-6 py-5">
          {/* Order summary */}
          <div className="mb-6 rounded-xl border border-[#e8b84b]/20 bg-[#e8b84b]/[0.04] p-4 text-sm">
            <div className="mb-2 flex justify-between text-white/70">
              <span>{t("checkout.product")}</span>
              <span className="text-white">{product.title}</span>
            </div>
            <div className="mb-2 flex justify-between text-white/70">
              <span>{t("checkout.size")}</span>
              <span className="text-white">{size || "—"}</span>
            </div>
            <div className="mb-2 flex justify-between text-white/70">
              <span>{t("checkout.quantity")}</span>
              <span className="text-white">1</span>
            </div>
            <div className="mt-3 flex justify-between border-t border-[#e8b84b]/20 pt-3 text-base font-bold">
              <span className="text-white">{t("checkout.total")}</span>
              <span className="text-[#e8b84b]">{product.priceGel} ₾</span>
            </div>
          </div>

          <form onSubmit={handleSubmit} noValidate className="space-y-4">
            {/* Size selector */}
            <div>
              <label className="mb-1.5 block text-xs font-semibold uppercase tracking-[0.06em] text-white/90">
                {t("checkout.size")} <span className="text-[#e8b84b]">*</span>
              </label>
              <div className="flex flex-wrap gap-2">
                {availableSizes.map((s) => (
                  <button
                    type="button"
                    key={s.size}
                    onClick={() => setSize(s.size)}
                    disabled={isSubmitting}
                    className={`min-w-[3rem] rounded-lg border px-3 py-2 text-sm font-bold uppercase transition ${
                      size === s.size
                        ? "border-[#e8b84b] bg-[#e8b84b] text-black"
                        : "border-white/20 text-white/80 hover:border-white/50"
                    }`}
                  >
                    {s.size}
                  </button>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <Field label={t("checkout.firstName")} required>
                <input
                  type="text"
                  required
                  autoComplete="given-name"
                  placeholder={t("checkout.firstNamePlaceholder")}
                  disabled={isSubmitting}
                  className={inputClass}
                  value={formData.name}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      name: e.target.value.replace(/[^a-zA-Z\s\-]/g, ""),
                    })
                  }
                />
              </Field>

              <Field label={t("checkout.surname")} required>
                <input
                  type="text"
                  required
                  autoComplete="family-name"
                  placeholder={t("checkout.surnamePlaceholder")}
                  disabled={isSubmitting}
                  className={inputClass}
                  value={formData.surname}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      surname: e.target.value.replace(/[^a-zA-Z\s\-]/g, ""),
                    })
                  }
                />
              </Field>
            </div>

            <Field
              label={t("buyProduct.personalIdLabel")}
              required
              hint={t("buyProduct.personalIdHint")}
            >
              <input
                type="text"
                required
                inputMode="numeric"
                maxLength={11}
                placeholder="01001234567"
                disabled={isSubmitting}
                className={inputClass}
                value={formData.personalNumber}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    personalNumber: e.target.value.replace(/\D/g, "").slice(0, 11),
                  })
                }
              />
            </Field>

            <Field label={t("checkout.email")} required hint={t("buyProduct.emailHint")}>
              <input
                type="email"
                required
                inputMode="email"
                autoComplete="email"
                placeholder={t("checkout.emailPlaceholder")}
                disabled={isSubmitting}
                className={inputClass}
                value={formData.email}
                onChange={(e) =>
                  setFormData({ ...formData, email: e.target.value })
                }
              />
            </Field>

            <Field label={t("checkout.phone")} required>
              <input
                type="tel"
                required
                inputMode="tel"
                autoComplete="tel"
                placeholder={t("checkout.phonePlaceholder")}
                disabled={isSubmitting}
                className={inputClass}
                value={formData.phone}
                onChange={(e) =>
                  setFormData({ ...formData, phone: e.target.value })
                }
              />
            </Field>

            <label className="flex cursor-pointer items-start gap-2 text-sm text-white/75">
              <input
                type="checkbox"
                required
                disabled={isSubmitting}
                checked={formData.terms}
                onChange={(e) =>
                  setFormData({ ...formData, terms: e.target.checked })
                }
                className="mt-[3px] accent-[#e8b84b]"
              />
              <span>
                {t("checkout.agreePrefix")}
                <a
                  href="/rules-and-terms"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-[#e8b84b] underline"
                >
                  {t("rulesAndTerms.title")}
                </a>
                {t("checkout.agreeSuffix")}
              </span>
            </label>

            {error ? (
              <div className="rounded-lg border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-200">
                {error}
              </div>
            ) : null}

            <button
              type="submit"
              disabled={isSubmitting}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#e8b84b] py-4 text-sm font-black uppercase tracking-[0.06em] text-black shadow-lg shadow-[#e8b84b]/20 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-70"
            >
              {isSubmitting ? (
                <>
                  <span
                    aria-hidden
                    className="inline-block h-4 w-4 animate-spin rounded-full border-2 border-black/20 border-t-black"
                  />
                  {submitLabel[loadingStage]}
                </>
              ) : (
                submitLabel[""]
              )}
            </button>

            <PaymentCardBadges className="pt-1" />
            <p className="text-center text-[11px] text-white/45">
              {t("buyProduct.secureNote")}
            </p>
          </form>
        </div>
      </div>
    </div>
  );
}

function Field({
  label,
  required,
  hint,
  children,
}: {
  label: string;
  required?: boolean;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="mb-1.5 block text-xs font-semibold uppercase tracking-[0.06em] text-white/90">
        {label} {required ? <span className="text-[#e8b84b]">*</span> : null}
      </label>
      {children}
      {hint ? <p className="mt-1 text-xs text-white/45">{hint}</p> : null}
    </div>
  );
}

const inputClass =
  "w-full rounded-lg border border-white/15 bg-white/[0.04] p-3 text-white outline-none transition focus:border-[#e8b84b] focus:bg-white/[0.06] disabled:opacity-50";
