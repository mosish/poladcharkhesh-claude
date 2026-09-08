# Data model

## The store

`data/store/` holds one JSON document per collection.

| File | Shape | Contents |
|---|---|---|
| `products.json` | array | The 68-reference catalogue |
| `company.json` | object | The authoritative company record |
| `content.json` | object | Editable site copy, Persian and English side by side |
| `seo.json` | object | Site-wide SEO defaults and the canonical hosts |
| `media.json` | object | Which referenced assets actually decode |
| `inquiries.json` | array | Enquiries (Phase G) |
| `audit.json` | array | Admin action log (Phase G) |

## Product

Field names are camelCase in JSON. Everything below `family` came across from
the reference database unchanged.

**Identity** — `id`, `code`, `slug`, `category`, `family`, `familyConfidence`,
`familySource`

**Localised** — `nameFa`, `nameEn`, `descriptionFa`, `descriptionEn`

**State** — `inStock`, `featured`, `isArchived`

**Principal dimensions** (mm) — `d` bore, `D` outside diameter, `B` width,
`rMin` corner radius

**Mass** — `weightKg`

**Load ratings** (kN) — `crKn` basic dynamic, `corKn` basic static

**Speeds** (r/min) — `speedGreaseRpm`, `speedOilRpm`, `thermalSpeedRatingRpm`

**Construction** — `cageMaterialFa` / `cageMaterialEn`, `sealingFa` /
`sealingEn`, `clearanceOptions`, `schematicType`

**Calculation factors** — `factorE`, `factorY`, `factorY0`, `factorY1`,
`factorY2`, `factorF0`. These are per-reference, not per-category: the
calculator reads them from the selected product rather than applying one
factor to every bearing.

**Commercial context** — `brands`, `applicationsFa`, `applicationsEn`,
`industryIds`

**Media** — `imageUrl`, `images`, `pdfUrl`

**Provenance** — `technicalSources`, each with `manufacturer`, `sourceType`,
`reference` and `verifiedAt`

**SEO** — `metaTitleFa`, `metaTitleEn`, `metaDescriptionFa`,
`metaDescriptionEn`

**Audit** — `createdAt`, `updatedAt`, `updatedBy`

`null` means "not specified" and is rendered as such. It is never filled in
with a plausible value.

## Family derivation

`family` is the one field the migration added. It is derived from the ISO/DIN
designation by `scripts/migrate_from_reference.py`, and stored alongside the
rule that produced it and a confidence.

The rules read the designation the way a parts desk does — digit count first,
because it disambiguates the series:

| Designation | Family | Why |
|---|---|---|
| `6xxx` | deep groove ball | 4-digit series 6 |
| `7xxx` | angular contact ball | 4-digit series 7 |
| `32xx` `33xx` `52xx` `53xx` | angular contact ball, double row | 4-digit |
| `12xx` `13xx` `22xx` `23xx` | self-aligning ball | 4-digit series 1 or 2 |
| `30xxx` `31xxx` `32xxx` `33xxx` | tapered roller | 5-digit |
| `222xx` `223xx` `230xx` `231xx` `232xx` | spherical roller | 5-digit |
| `511xx` – `514xx` | thrust ball | 5-digit |
| `292xx` `293xx` `294xx` | spherical roller thrust | 5-digit |
| `NU` `NJ` `NUP` `N` `NCF` | cylindrical roller | prefix |
| `NA` `NK` `NKI` `KR` | needle roller / track roller | prefix |
| `C…` / CARB | toroidal roller | prefix |
| `UCP` `UCF` `UCFL` `UCT` | bearing unit | prefix |
| `SNL` `SN` `SAF` | bearing housing | prefix |
| `TC…` / Simmerring | oil seal | seal designation |
| LGMT, grease wording | lubricant | product wording |
| `LM` `L` `M` `HM` sets | tapered roller | imperial cup/cone |

Note that `3308` is a double row angular contact bearing and `32210` is a
tapered roller bearing. The digit count is what separates them, which is why
the rules check length before prefix.

All 68 references resolve at high confidence.

## What was flagged, and not changed

`data/migration-review.json` records twelve references where the imported
`schematicType` disagrees with the product's own name and designation. Nothing
was corrected — the brief's data principle is detect → flag → source → compare
→ human review → approve → update, and only the first two steps belong to a
migration.

| Reference | Stored | Derived family |
|---|---|---|
| 7312 BECBM | deep-groove | angular-contact-ball |
| 7210 BECBP / P6 | deep-groove | angular-contact-ball |
| 3308 A-2Z/C3 | deep-groove | angular-contact-ball |
| NSK 7010 CTYNDBLP4 | deep-groove | angular-contact-ball |
| NTN 5206 S | deep-groove | angular-contact-ball |
| 1309 EKTN9 | deep-groove | self-aligning-ball |
| NA 4910 | cylindrical | needle-roller |
| INA NK 25/20 | cylindrical | needle-roller |
| INA KR 35 PP | cylindrical | needle-roller |
| SKF C 2215 K | cylindrical | toroidal-roller |
| SKF LGMT 2 | deep-groove | lubricant |
| Mobilith SHC 220 | spherical | lubricant |

This matters beyond the drawing. The brief requires family-specific
calculation, and an angular contact bearing evaluated with deep-groove logic
returns a wrong equivalent load. The derived `family` is what the calculator
will use; `schematicType` stays as imported until someone approves a change.

## Verification

The migration checks itself. Every numeric field, every text field and every
technical-source record is compared against the source database after writing:

```
verification: 0 drifts across 68 products x 25 fields
technical source records preserved: 68
```

Re-run with:

```bash
python3 scripts/migrate_from_reference.py <path-to-reference.db> data/store
```

## Media

`media.json` records which referenced assets decode. It is written by
`scripts/audit_assets.py` and read by `Storage\MediaRepository`.

At the time of migration the answer was **none of them**:

```
referenced assets : 29
usable            : 0
unusable          : 29
products with no usable image: 68
```

Every product image in the reference repository has been through a UTF-8 text
decode at some point in its history. Each byte that was not valid UTF-8 was
replaced with the replacement character U+FFFD (`EF BF BD`), which is why the
files are roughly twice their original size and why the RIFF length fields no
longer match. The compressed image data is destroyed; this is not recoverable
by re-encoding.

The files still serve with a 200 and the right MIME type, so a browser requests
them, fails to decode them, and renders nothing. That is why the check exists
as a build step rather than as a runtime guess.

Until original photography is supplied, product cards render the family's
technical section symbol from `Support\Schematics`. That was chosen over the
two alternatives deliberately: a broken image reads as neglect, and a stock
photograph would be a picture of a part the company is not selling.

## Industry links

`industryIds` on every product contains the same two values, `steel` and
`mining`. That is a seeding artefact, not curation, so the industries section
links to bearing **families** rather than to product lists. Presenting
per-industry product lists from this data would be presenting noise as
knowledge.
