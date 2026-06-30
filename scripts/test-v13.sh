#!/usr/bin/env bash
# Test logica v1.3.0 (overlap date, senza WordPress).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

ERRORS=0

fail() {
	echo "FAIL: $1" >&2
	ERRORS=$((ERRORS + 1))
}

pass() {
	echo "OK:   $1"
}

dates_overlap() {
	local in1="$1" out1="$2" in2="$3" out2="$4"
	[[ "$in1" < "$out2" && "$in2" < "$out1" ]]
}

echo "=== Test logica v1.3.0 ==="

# Overlap: stesso periodo
if dates_overlap "2026-07-01" "2026-07-05" "2026-07-01" "2026-07-05"; then
	pass "Overlap periodo identico"
else
	fail "Overlap periodo identico"
fi

# Overlap parziale
if dates_overlap "2026-07-01" "2026-07-10" "2026-07-05" "2026-07-15"; then
	pass "Overlap parziale"
else
	fail "Overlap parziale"
fi

# Adiacente check-out = check-in altro: NO overlap (come PHP strtotime logic)
if dates_overlap "2026-07-01" "2026-07-05" "2026-07-05" "2026-07-10"; then
	fail "Date adiacenti non devono sovrapporsi"
else
	pass "Date adiacenti senza overlap"
fi

# Separati
if dates_overlap "2026-07-01" "2026-07-05" "2026-07-10" "2026-07-15"; then
	fail "Periodi separati non devono sovrapporsi"
else
	pass "Periodi separati"
fi

echo "--- File v1.3.0 ---"
for f in \
	"includes/class-booking-expiry.php" \
	"includes/class-privacy.php" \
	"templates/booking-form.php" \
	"public/js/public.js"
do
	if [[ -f "$f" ]]; then
		pass "Presente: $f"
	else
		fail "Manca: $f"
	fi
done

echo "--- Consenso privacy nel form ---"
if grep -q 'privacy_consent' templates/booking-form.php && grep -q 'Privacy::get_consent_label' templates/booking-form.php; then
	pass "Checkbox privacy nel template"
else
	fail "Checkbox privacy mancante nel template"
fi

echo "--- Validazione server consenso ---"
if grep -q "privacy_consent" includes/class-booking.php; then
	pass "Validazione privacy in Booking::create"
else
	fail "Validazione privacy mancante"
fi

echo "--- Scadenza richieste ---"
if grep -q 'pending_hold_hours' includes/class-settings.php && grep -q 'expire_pending' includes/class-booking-expiry.php; then
	pass "Scadenza richieste configurata"
else
	fail "Scadenza richieste incompleta"
fi

echo "--- GDPR Privacy Tools ---"
if grep -q 'wp_privacy_personal_data_exporters' includes/class-privacy.php && grep -q 'wp_privacy_personal_data_erasers' includes/class-privacy.php; then
	pass "Hook Privacy Tools registrati"
else
	fail "Hook Privacy Tools mancanti"
fi

echo "--- Notice duplicato rimosso ---"
if grep -q 'maybe_health_notice' includes/class-plugin.php; then
	fail "maybe_health_notice ancora presente in class-plugin.php"
else
	pass "Notice duplicato rimosso da class-plugin.php"
fi

if grep -q 'duplicate_install_notice' includes/class-github-updater.php; then
	pass "Notice duplicati gestito solo da GitHub_Updater"
else
	fail "duplicate_install_notice mancante in GitHub_Updater"
fi

echo "--- JS validazione date ---"
if grep -q 'datesOverlap' public/js/public.js && grep -q 'datesBlocked' includes/class-assets.php; then
	pass "Validazione date bloccate in frontend"
else
	fail "Validazione date bloccate incompleta"
fi

echo
if [[ "$ERRORS" -gt 0 ]]; then
	echo "=== RISULTATO: $ERRORS errori ===" >&2
	exit 1
fi

echo "=== RISULTATO: tutti i test v1.3.0 superati ==="
exit 0
