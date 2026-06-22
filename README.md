# Casa Vacanza Prenotazioni

Plugin WordPress per prenotazioni case vacanza. **Evolo Digital Studio**

Repository: https://github.com/evolofabio/casa-vacanza-prenotazioni

## Installazione

```bash
cd wp-content/plugins
git clone https://github.com/evolofabio/casa-vacanza-prenotazioni.git
```

Attiva da **Plugin** in WordPress.

## Aggiornare

```bash
cd wp-content/plugins/casa-vacanza-prenotazioni
git pull origin main
```

Oppure da WordPress: **Plugin → Aggiorna** (dopo una GitHub Release).

## Pubblicare versione

```bash
# Aggiorna Version in casa-vacanza-prenotazioni.php
git add . && git commit -m "Release 1.0.x"
git push origin main
git tag v1.0.x && git push origin v1.0.x
```

Crea la **Release** su GitHub dal tag.

## Shortcode

- `[cvp_search_bar]` — barra ricerca
- `[cvp_search_results]` — risultati
- `[cvp_apartment_card id="123"]` — card appartamento
- `[cvp_booking_form apartment_id="123"]` — form prenotazione
