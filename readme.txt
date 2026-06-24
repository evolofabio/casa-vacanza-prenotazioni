=== Casa Vacanza Prenotazioni ===
Contributors: evolodigitalstudio
Tags: prenotazioni, booking, vacation rental, casa vacanza, elementor
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Sistema completo di prenotazioni per case vacanza.

== Description ==

Plugin WordPress per gestire appartamenti vacanza, disponibilità, prenotazioni e area operatore.

**Funzionalità principali:**

* Custom Post Type **Appartamenti** (galleria, prezzo, capienza, servizi)
* Custom Post Type **Prenotazioni** con stati: In attesa, Confermata, Rifiutata, Annullata
* Calendario disponibilità con blocco automatico su prenotazioni in attesa/confermate
* Barra ricerca (check-in, check-out, ospiti)
* Card appartamento con form prenotazione integrato
* Email automatiche a cliente e operatore
* Dashboard operatore in wp-admin
* Ruolo **Gestore Prenotazioni**
* Shortcode, blocchi Gutenberg e widget Elementor (categoria "Casa Vacanza")

== Installation ==

1. Carica la cartella `casa-vacanza-prenotazioni` in `/wp-content/plugins/`
2. Attiva il plugin da **Plugin**
3. Vai su **Casa Vacanza → Impostazioni** per configurare email, valuta e pagina risultati
4. Crea i tuoi appartamenti da **Casa Vacanza → Appartamenti**

== Shortcodes ==

* `[cvp_search_bar]` — Barra ricerca
* `[cvp_search_results]` — Pagina risultati (include barra + griglia)
* `[cvp_apartment_card id="123"]` — Card singolo appartamento
* `[cvp_booking_form apartment_id="123"]` — Form prenotazione

== Blocchi Gutenberg ==

Cerca "Casa Vacanza" nell'inserter blocchi:

* Barra Ricerca Casa Vacanza
* Card Appartamento
* Form Prenotazione
* Risultati Ricerca Appartamenti

== Elementor ==

Widget disponibili nella categoria **Casa Vacanza**.

== Changelog ==

= 1.1.0 =
* Campo posti letto per ogni appartamento (admin, Elementor, modifica rapida)
* Periodo di apertura (date disponibili) e blocchi manuali nel meta box Disponibilità
* Calendario admin aggiornato automaticamente con prenotazioni in attesa/confermate
* Validazione date nel form prenotazione e in modifica prenotazione admin
* Dynamic tag Elementor "Posti letto"

= 1.0.0 =
* Release iniziale
