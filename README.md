# BB Reviews

WordPress-Plugin von Büro Blanko. Bewertungen von Google, OneDoc, Sanasearch und anderen Plattformen werden von Hand angelegt (Copy/Paste) und per Shortcode gezeigt. Es lädt nichts von fremden Servern, braucht keine Einwilligung und keinen API-Schlüssel.

Stand 11.11.2026, Version 0.2.0.

## Pflege

Backend → Bewertungen → Bewertung hinzufügen. Felder: Name (Titel), Überschrift (optional), Text, Sterne (1–5 oder keine), Plattform, Datum der Bewertung, Link (optional). Nur veröffentlichte Einträge erscheinen. Es gibt keine Einzelseiten und kein Archiv.

## Shortcode

    [bb_reviews]

| Attribut | Standard | Bedeutung |
|---|---|---|
| layout | karussell | `karussell` oder `raster` |
| sichtbar | 3 | Karten nebeneinander am Desktop (Tablet 2, Handy 1) |
| anzahl | 12 | höchstens so viele Bewertungen |
| quelle | (alle) | z. B. `google` oder `google,onedoc` |
| sortierung | datum | `datum` (neueste zuerst), `manuell` (Feld Reihenfolge), `zufall` |
| autoplay | 5 | Sekunden bis zum Weiterblättern, `0` = aus |
| pfeile | ja | Pfeile über dem Karussell |
| sterne | ja | Sterne zeigen |
| datum | ja | Datum zeigen |
| zeilen | 6 | Text nach so vielen Zeilen kürzen („Mehr lesen“), `0` = nie |
| titel | (leer) | Zeile über den Bewertungen |

## Aussehen anpassen

Über CSS-Variablen auf `.bbr`, z. B. im individuellen CSS des Themes:

    .bbr{--bbr-accent:#320751;--bbr-radius:20px;--bbr-star:#f5a623;--bbr-border:rgba(50,7,81,.22)}

Weitere: `--bbr-text`, `--bbr-bg`, `--bbr-gap`, `--bbr-pad`, `--bbr-align` (center/left), `--bbr-more-align`.

## Erweitern

Plattformen über den Filter `bb_reviews_sources` (Schlüssel → Name). Import per Code: Eintrag vom Typ `bb_review` anlegen, dann `BB_Reviews::store($id, [...])`.

## Bewusst nicht drin

- Kein Abruf von Google oder anderen Plattformen, also auch kein automatischer Gesamtwert.
- Kein Schema.org-Markup: Selbst eingestellte Bewertungen der eigenen Firma sind für Sterne in der Google-Suche nicht zulässig.

## Updates und Releases

Das Plugin aktualisiert sich über die öffentlichen Releases dieses Repos (`Update URI` im Plugin-Kopf, `includes/updater.php`). Auf der Website liegen keine GitHub-Zugangsdaten. In der Plugin-Liste gibt es dazu den Link „Nach Updates suchen“.

Neues Release: Version im Plugin-Kopf und in `BB_Reviews::VERSION` erhöhen, committen, Tag `v<version>` pushen. Die Action `.github/workflows/release.yml` prüft die Syntax, baut `bb-reviews-<version>.zip` und veröffentlicht das Release. Lokal bauen: `python3 tools/package.py` (legt die Zip in `dist/`).

## Dateien

`bb-reviews/bb-reviews.php` (Inhaltstyp, Felder, Shortcode), `bb-reviews/includes/updater.php`, `bb-reviews/assets/bb-reviews.css`, `bb-reviews/assets/bb-reviews.js`, `tools/package.py`.

Lizenz: GPL-2.0-or-later.
