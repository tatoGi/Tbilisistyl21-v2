import Image, { type StaticImageData } from "next/image";

export default function ContentFigure({
  src,
  alt,
  aspect = "video",
  priority = false,
}: {
  src: string | StaticImageData;
  alt: string;
  aspect?: "video" | "square" | "portrait";
  priority?: boolean;
}) {
  const aspectClass =
    aspect === "square"
      ? "aspect-square"
      : aspect === "portrait"
        ? "aspect-[3/4]"
        : "aspect-video";

  return (
    <figure
      className={`relative w-full overflow-hidden rounded-2xl border border-white/10 ${aspectClass}`}
    >
      <Image
        src={src}
        alt={alt}
        fill
        priority={priority}
        className="object-cover"
        sizes="(max-width: 768px) 100vw, 900px"
      />
    </figure>
  );
}
