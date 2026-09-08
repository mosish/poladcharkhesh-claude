#!/usr/bin/env python3
"""
Verify that every asset the catalogue references actually decodes.

This exists because the reference repository's product photography does not.
Every one of the 29 images had been passed through a UTF-8 text decode at some
point in its history: each byte that was not valid UTF-8 was replaced with the
replacement character U+FFFD (EF BF BD), which destroys the compressed image
data irreversibly. The files are the right size and serve with the right MIME
type, so a browser requests them, fails to decode them, and renders nothing.

Nothing here rewrites the catalogue. It records which referenced files are
usable in the media registry, and the interface falls back to the family
schematic for the rest — a deliberate technical drawing rather than a broken
image or an invented stock photo.

Usage:  python3 scripts/audit_assets.py [--store data/store] [--public public]
"""

from __future__ import annotations

import argparse
import json
import pathlib
import sys

REPLACEMENT = b"\xef\xbf\xbd"


def probe(path: pathlib.Path) -> tuple[bool, str]:
    """Return (usable, reason)."""
    if not path.is_file():
        return False, "missing"

    data = path.read_bytes()
    if len(data) < 32:
        return False, "truncated"

    suffix = path.suffix.lower()

    if suffix == ".webp":
        if data[:4] != b"RIFF" or data[8:12] != b"WEBP":
            if REPLACEMENT in data[:64]:
                return False, "utf-8 round-trip corruption (U+FFFD in header)"
            return False, "not a RIFF/WEBP container"
        declared = int.from_bytes(data[4:8], "little")
        if abs(declared - (len(data) - 8)) > 2:
            return False, f"RIFF length {declared} does not match file ({len(data) - 8})"
    elif suffix in {".png"}:
        if data[:8] != b"\x89PNG\r\n\x1a\n":
            return False, "bad PNG signature"
    elif suffix in {".jpg", ".jpeg"}:
        if data[:2] != b"\xff\xd8":
            return False, "bad JPEG signature"
    elif suffix == ".svg":
        head = data[:512].lower()
        if b"<svg" not in head:
            return False, "no <svg> element"
    elif suffix == ".woff2":
        if data[:4] != b"wOF2":
            return False, "bad woff2 signature"

    # A high density of replacement characters is the signature of a binary
    # file that has been through a text decode.
    if suffix not in {".svg"}:
        hits = data.count(REPLACEMENT)
        if hits > len(data) / 400:
            return False, f"utf-8 round-trip corruption ({hits} replacement chars)"

    return True, "ok"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--store", default="data/store")
    parser.add_argument("--public", default="public")
    args = parser.parse_args()

    store = pathlib.Path(args.store)
    public = pathlib.Path(args.public)

    products = json.loads((store / "products.json").read_text(encoding="utf-8"))

    referenced: dict[str, list[str]] = {}
    for product in products:
        urls = []
        if product.get("imageUrl"):
            urls.append(product["imageUrl"])
        urls.extend(product.get("images") or [])
        if product.get("pdfUrl"):
            urls.append(product["pdfUrl"])
        for url in dict.fromkeys(urls):
            referenced.setdefault(url, []).append(product["code"])

    usable: list[str] = []
    broken: list[dict[str, object]] = []

    for url in sorted(referenced):
        path = public / url.lstrip("/")
        ok, reason = probe(path)
        if ok:
            usable.append(url)
        else:
            broken.append({
                "path": url,
                "reason": reason,
                "bytes": path.stat().st_size if path.is_file() else 0,
                "usedBy": referenced[url],
            })

    registry = {
        "generatedAt": __import__("datetime").datetime.now(
            __import__("datetime").timezone.utc
        ).isoformat(timespec="seconds"),
        "usable": usable,
        "broken": [{k: v for k, v in b.items() if k != "usedBy"} for b in broken],
    }
    (store / "media.json").write_text(
        json.dumps(registry, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )

    print(f"  referenced assets : {len(referenced)}")
    print(f"  usable            : {len(usable)}")
    print(f"  unusable          : {len(broken)}")

    if broken:
        reasons: dict[str, int] = {}
        for entry in broken:
            reasons[str(entry["reason"])] = reasons.get(str(entry["reason"]), 0) + 1
        print("\n  reasons:")
        for reason, count in sorted(reasons.items(), key=lambda kv: -kv[1]):
            print(f"    {count:3}  {reason}")
        affected = sorted({code for entry in broken for code in entry["usedBy"]})
        print(f"\n  products with no usable image: {len(affected)}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
