#!/usr/bin/env python3
"""
Migrate the canonical catalogue out of the reference SQLite database
(mosish/PoladCharkhesh) into the flat-file JSON store used by this build.

Principles (brief section 68):
  - Engineering values are copied verbatim. Nothing is regenerated, rounded or
    "repaired".
  - The bearing family is DERIVED from the ISO/DIN designation and stored in a
    NEW field. The reference `schematicType` is preserved untouched alongside it.
  - Where the derived family disagrees with the stored schematic type, the record
    is written to a review report for a human to approve. It is never silently
    rewritten.

Usage:  python3 scripts/migrate_from_reference.py <path-to-reference.db> <out-dir>
"""

from __future__ import annotations

import json
import re
import sqlite3
import sys
from pathlib import Path

# --------------------------------------------------------------------------
# Family derivation
# --------------------------------------------------------------------------

# The thirteen engineering families the brief asks for, plus `toroidal-roller`,
# which the catalogue contains (SKF CARB) and which is genuinely its own family.
FAMILIES = [
    "deep-groove-ball",
    "angular-contact-ball",
    "self-aligning-ball",
    "tapered-roller",
    "spherical-roller",
    "toroidal-roller",
    "cylindrical-roller",
    "needle-roller",
    "thrust-ball",
    "spherical-thrust-roller",
    "bearing-unit",
    "bearing-housing",
    "oil-seal",
    "lubricant",
]


BRANDS = {
    "SKF", "FAG", "INA", "NSK", "NTN", "KOYO", "JTEKT", "NACHI", "TIMKEN",
    "FYH", "SCHAEFFLER", "MOBILITH", "MOBIL", "CORTECO", "FREUDENBERG", "EXPLORER",
}


def _designation(code: str) -> str:
    """Reduce a catalogue code to its compact ISO/DIN designation.

    'NU 208 ECP / C3' -> 'NU208ECP'   'TIMKEN LM11749 / LM11710' -> 'LM11749'
    'SKF C 2215 K CARB(R)' -> 'C2215K'
    """
    cleaned = code.upper()
    cleaned = re.sub(r"\(.*?\)", " ", cleaned)            # drop parenthetical notes
    cleaned = cleaned.split("/")[0]                        # drop the /Q, /W33 half
    cleaned = re.sub(r"[®™]", " ", cleaned)
    tokens = [t for t in re.split(r"[\s,]+", cleaned) if t and t not in BRANDS]
    # Designations are written with inconsistent spacing ("NU 208" / "NU208"),
    # so join back up and match on the compact form.
    return "".join(tokens)


def derive_family(code: str, name_en: str) -> tuple[str | None, str, str]:
    """Return (family, confidence, rule) derived from the designation."""
    token = _designation(code)
    upper = f"{code} {name_en}".upper()

    # --- non-bearing product lines, identified by product wording -----------
    if "GREASE" in upper or "LUBRICAN" in upper or token.startswith("LGMT"):
        return "lubricant", "high", "product wording: grease/lubricant"
    if re.match(r"^(TC|TCN|SC|TB|VA|HMSA|CR)\d", token) or "OIL SEAL" in upper:
        return "oil-seal", "high", "radial shaft seal designation (TC/SC/HMSA family)"
    if "SIMMERRING" in upper or re.search(r"\bTC\s*\d+\s*[X×]\s*\d+\s*[X×]\s*\d+", upper):
        return "oil-seal", "high", "Simmerring / TC dxDxB radial shaft seal dimensions"

    # --- housings and units -------------------------------------------------
    if re.match(r"^(UCP|UCF|UCFL|UCT|UCFC|UC)\d", token):
        return "bearing-unit", "high", "designation prefix UC* (insert bearing unit)"
    if re.match(r"^(SNL|SN|SAF|SD|FSNL)\d", token) or "PLUMMER" in upper or "SPLIT PLUMMER" in upper:
        return "bearing-housing", "high", "designation prefix SNL/SN/SAF (plummer housing)"

    # --- roller bearings by letter prefix ----------------------------------
    if re.match(r"^(NA|NK|NKI|NKS|RNA)\d", token):
        return "needle-roller", "high", "designation prefix NA/NK/NKI (needle roller)"
    if re.match(r"^(KR|KRV|NUKR|CF)\d", token):
        return "needle-roller", "medium", "designation prefix KR (stud type track roller / cam follower)"
    if re.match(r"^C\d", token) or "CARB" in upper or "TOROIDAL" in upper:
        return "toroidal-roller", "high", "designation prefix C / CARB toroidal"
    if re.match(r"^(NU|NJ|NUP|NCF|NNU|NN|N)\d", token):
        return "cylindrical-roller", "high", "designation prefix NU/NJ/NUP/N (cylindrical roller)"

    # --- imperial tapered sets ---------------------------------------------
    if re.match(r"^(LM|L|M|HM|JLM|JM|SET)\d", token):
        return "tapered-roller", "high", "imperial tapered cup/cone designation"

    # --- numeric designations ----------------------------------------------
    m = re.match(r"^(\d+)", token)
    if m:
        digits = m.group(1)
        n = len(digits)

        if n >= 5:
            p3 = digits[:3]
            p2 = digits[:2]
            if p2 in ("30", "31", "32", "33"):
                return "tapered-roller", "high", f"5-digit series {p2}xxx (tapered roller)"
            if p3 in ("222", "223", "230", "231", "232", "213", "240", "241", "248", "249"):
                return "spherical-roller", "high", f"5-digit series {p3}xx (spherical roller)"
            if p3 in ("511", "512", "513", "514"):
                return "thrust-ball", "high", f"5-digit series {p3}xx (thrust ball)"
            if p3 in ("292", "293", "294"):
                return "spherical-thrust-roller", "high", f"5-digit series {p3}xx (spherical roller thrust)"
            return None, "none", f"unrecognised 5-digit series {digits}"

        if n == 4:
            p1, p2 = digits[0], digits[:2]
            if p1 == "6":
                return "deep-groove-ball", "high", "4-digit series 6xxx (deep groove ball)"
            if p1 == "7":
                return "angular-contact-ball", "high", "4-digit series 7xxx (angular contact ball)"
            if p2 in ("32", "33", "52", "53"):
                return "angular-contact-ball", "high", f"4-digit series {p2}xx (double row angular contact)"
            if p1 in ("1", "2"):
                return "self-aligning-ball", "high", f"4-digit series {p1}xxx (self-aligning ball)"
            if p2 in ("51", "52", "53", "54"):
                return "thrust-ball", "high", f"4-digit series {p2}xx (thrust ball)"
            return None, "none", f"unrecognised 4-digit series {digits}"

    return None, "none", "no rule matched"


# Which schematic types are consistent with which derived family. Used only to
# decide whether a record needs human review — never to rewrite data.
COMPATIBLE_SCHEMATIC = {
    "deep-groove-ball": {"deep-groove"},
    "angular-contact-ball": {"angular-contact"},
    "self-aligning-ball": {"self-aligning-ball"},
    "tapered-roller": {"tapered"},
    "spherical-roller": {"spherical"},
    "toroidal-roller": {"carb"},
    "cylindrical-roller": {"cylindrical"},
    "needle-roller": {"needle"},
    "thrust-ball": {"thrust"},
    "spherical-thrust-roller": {"spherical-thrust", "thrust"},
    "bearing-unit": {"pillow-block"},
    "bearing-housing": {"pillow-block"},
    "oil-seal": {"oil-seal"},
    "lubricant": set(),
}


# --------------------------------------------------------------------------
# Column mapping: sqlite snake_case -> JSON camelCase
# --------------------------------------------------------------------------

SCALARS = {
    "id": "id", "code": "code", "slug": "slug", "category": "category",
    "name_fa": "nameFa", "name_en": "nameEn",
    "description_fa": "descriptionFa", "description_en": "descriptionEn",
    "d_inner": "d", "d_outer": "D", "b_width": "B", "weight_kg": "weightKg",
    "cr_kn": "crKn", "cor_kn": "corKn",
    "speed_grease_rpm": "speedGreaseRpm", "speed_oil_rpm": "speedOilRpm",
    "thermal_speed_rating_rpm": "thermalSpeedRatingRpm",
    "cage_material_fa": "cageMaterialFa", "cage_material_en": "cageMaterialEn",
    "sealing_fa": "sealingFa", "sealing_en": "sealingEn",
    "schematic_type": "schematicType", "r_min": "rMin",
    "calculation_factor_e": "factorE", "calculation_factor_y": "factorY",
    "calculation_factor_y0": "factorY0", "calculation_factor_y1": "factorY1",
    "calculation_factor_y2": "factorY2", "calculation_factor_f0": "factorF0",
    "image_url": "imageUrl", "pdf_url": "pdfUrl",
    "meta_title_fa": "metaTitleFa", "meta_title_en": "metaTitleEn",
    "meta_description_fa": "metaDescriptionFa", "meta_description_en": "metaDescriptionEn",
    "created_at": "createdAt", "updated_at": "updatedAt", "updated_by": "updatedBy",
}
BOOLS = {"in_stock": "inStock", "featured": "featured", "is_archived": "isArchived"}
JSON_ARRAYS = {
    "clearance_options": "clearanceOptions", "images": "images", "brands": "brands",
    "applications_fa": "applicationsFa", "applications_en": "applicationsEn",
    "industry_ids": "industryIds", "technical_sources": "technicalSources",
}


def parse_json_field(raw, fallback):
    if raw in (None, ""):
        return fallback
    try:
        value = json.loads(raw)
    except (json.JSONDecodeError, TypeError):
        return fallback
    return value if value is not None else fallback


def migrate(db_path: Path, out_dir: Path) -> None:
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row

    products, review = [], []

    for row in conn.execute("SELECT * FROM products ORDER BY category, code"):
        rec = {}
        for col, key in SCALARS.items():
            rec[key] = row[col]
        for col, key in BOOLS.items():
            rec[key] = bool(row[col])
        for col, key in JSON_ARRAYS.items():
            rec[key] = parse_json_field(row[col], [])

        family, confidence, rule = derive_family(rec["code"], rec["nameEn"] or "")
        rec["family"] = family
        rec["familyConfidence"] = confidence
        rec["familySource"] = "derived-from-designation"

        schematic = rec["schematicType"]
        compatible = COMPATIBLE_SCHEMATIC.get(family or "", set())
        if family is None:
            review.append({
                "code": rec["code"], "slug": rec["slug"], "nameEn": rec["nameEn"],
                "issue": "family-underivable", "storedSchematicType": schematic,
                "derivedFamily": None, "rule": rule,
                "recommendation": "Set the family by hand in the admin panel.",
            })
        elif family == "lubricant":
            if schematic:
                review.append({
                    "code": rec["code"], "slug": rec["slug"], "nameEn": rec["nameEn"],
                    "issue": "schematic-on-non-bearing", "storedSchematicType": schematic,
                    "derivedFamily": family, "rule": rule,
                    "recommendation": "Clear schematicType; a lubricant has no bearing cross-section.",
                })
        elif schematic not in compatible:
            review.append({
                "code": rec["code"], "slug": rec["slug"], "nameEn": rec["nameEn"],
                "issue": "schematic-family-mismatch", "storedSchematicType": schematic,
                "derivedFamily": family, "rule": rule,
                "recommendation": f"schematicType should most likely be one of {sorted(compatible)}.",
            })

        products.append(rec)

    company = parse_json_field(
        conn.execute("SELECT data FROM company_info WHERE id='main'").fetchone()["data"], {}
    )
    seo = parse_json_field(
        conn.execute("SELECT data FROM seo_config WHERE id='main'").fetchone()["data"], {}
    )
    cms_row = conn.execute("SELECT data FROM cms_content WHERE id='main'").fetchone()
    cms = parse_json_field(cms_row["data"], {}) if cms_row else {}

    out_dir.mkdir(parents=True, exist_ok=True)

    def write(name, payload):
        path = out_dir / name
        path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"  wrote {path.name:22} {path.stat().st_size:>8,} bytes")

    write("products.json", products)
    write("company.json", company)
    write("seo.json", seo)
    write("content.json", cms)
    write("inquiries.json", [])
    write("audit.json", [])
    write("media.json", [])

    report_path = out_dir.parent / "migration-review.json"
    report_path.write_text(
        json.dumps({
            "source": str(db_path),
            "productCount": len(products),
            "flagged": len(review),
            "note": "Nothing below was modified. These records need human approval before any change.",
            "records": review,
        }, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    print(f"\n  {len(products)} products migrated, {len(review)} flagged for review")
    counts = {}
    for p in products:
        counts[p["family"] or "UNRESOLVED"] = counts.get(p["family"] or "UNRESOLVED", 0) + 1
    print("\n  derived families:")
    for fam, count in sorted(counts.items(), key=lambda kv: -kv[1]):
        print(f"    {fam:26} {count:3}")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    migrate(Path(sys.argv[1]), Path(sys.argv[2]))
