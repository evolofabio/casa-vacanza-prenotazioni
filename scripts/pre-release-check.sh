#!/usr/bin/env bash
# Verifiche obbligatorie prima di ogni release.
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

echo "=== Casa Vacanza Prenotazioni — pre-release check ==="
echo "Root: $ROOT"
echo

# 1. PHP syntax
echo "--- PHP syntax ---"
if ! command -v php >/dev/null 2>&1; then
	fail "php non trovato nel PATH"
else
	while IFS= read -r -d '' file; do
		if ! php -l "$file" >/dev/null 2>&1; then
			php -l "$file" >&2 || true
			fail "Syntax error in $file"
		fi
	done < <(find . -name '*.php' ! -path './.git/*' -print0)
	if [[ "$ERRORS" -eq 0 ]]; then
		pass "Tutti i file PHP sono validi"
	fi
fi

# 2. Versioni allineate
echo "--- Version alignment ---"
MAIN_FILE="casa-vacanza-prenotazioni.php"
HEADER_VERSION=$(grep -E '^\s*\*\s*Version:' "$MAIN_FILE" | head -1 | sed -E 's/.*Version:[[:space:]]*//' | tr -d ' ')
DEFINE_VERSION=$(grep -E "define\(\s*'CVP_VERSION'" "$MAIN_FILE" | grep -oE "'[0-9]+\.[0-9]+\.[0-9]+'" | tr -d "'" | tail -1)
README_VERSION=$(grep -E '^Stable tag:' readme.txt | sed -E 's/Stable tag:[[:space:]]*//')

if [[ "$HEADER_VERSION" != "$DEFINE_VERSION" ]]; then
	fail "Version header ($HEADER_VERSION) != CVP_VERSION ($DEFINE_VERSION)"
else
	pass "Header e CVP_VERSION: $HEADER_VERSION"
fi

if [[ "$HEADER_VERSION" != "$README_VERSION" ]]; then
	fail "Version header ($HEADER_VERSION) != readme Stable tag ($README_VERSION)"
else
	pass "readme Stable tag: $README_VERSION"
fi

# 3. Guard anti-duplicato
echo "--- Safety guards ---"
if grep -q "CVP_LOADED" "$MAIN_FILE"; then
	pass "Guard CVP_LOADED presente"
else
	fail "Manca guard CVP_LOADED nel file principale"
fi

if grep -q '<<<<<<<' . -r --include='*.php' --include='*.js' --include='*.css' 2>/dev/null; then
	fail "Trovati marker di conflitto git nel codice"
else
	pass "Nessun marker di conflitto git"
fi

# 4. Widget Elementor
echo "--- Elementor widgets ---"
WIDGET_DIR="elementor/widgets"
for widget in "$WIDGET_DIR"/*.php; do
	basename=$(basename "$widget")
	if ! grep -q 'extends Cvp_Widget_Base' "$widget"; then
		fail "$basename non estende Cvp_Widget_Base"
	fi
done
if [[ -f "elementor/class-cvp-widget-base.php" ]]; then
	pass "Base widget e estensioni OK"
else
	fail "Manca elementor/class-cvp-widget-base.php"
fi

# 5. Namespace nelle view/template
echo "--- View namespace ---"
RISKY=0
while IFS= read -r line; do
	RISKY=1
	echo "$line" >&2
done < <(grep -rn --include='*.php' -E '[^\\](Post_Types|Settings|Shortcodes|Assets|Apartment_Meta|Availability)::' admin templates 2>/dev/null || true)
if [[ "$RISKY" -eq 0 ]]; then
	pass "admin/ e templates/ usano namespace completo"
else
	fail "Trovate classi senza namespace \\CVP\\ in view/template"
fi

# 6. File obbligatori
echo "--- Required files ---"
REQUIRED=(
	"casa-vacanza-prenotazioni.php"
	"INSTALLAZIONE.txt"
	"uninstall.php"
	"includes/class-plugin.php"
	"includes/class-pricing.php"
	"includes/class-booking-expiry.php"
	"includes/class-privacy.php"
	"includes/class-github-updater.php"
	"public/css/public.css"
	"public/js/public.js"
)
for f in "${REQUIRED[@]}"; do
	if [[ ! -f "$f" ]]; then
		fail "File mancante: $f"
	fi
done
if [[ "$ERRORS" -eq 0 ]]; then
	pass "File obbligatori presenti"
fi

# 7. Build e struttura zip (come CI release)
echo "--- Zip structure ---"
BUILD_DIR=$(mktemp -d)
trap 'rm -rf "$BUILD_DIR"' EXIT

mkdir -p "$BUILD_DIR/casa-vacanza-prenotazioni"
rsync -a \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='*.zip' \
	--exclude='.DS_Store' \
	--exclude='scripts' \
	--exclude='tools' \
	./ "$BUILD_DIR/casa-vacanza-prenotazioni/"

ZIP_MAIN="$BUILD_DIR/casa-vacanza-prenotazioni/casa-vacanza-prenotazioni.php"
if [[ ! -f "$ZIP_MAIN" ]]; then
	fail "Zip build: manca casa-vacanza-prenotazioni.php nella root cartella"
else
	pass "Zip build: file principale nella cartella corretta"
fi

if [[ -d "$BUILD_DIR/casa-vacanza-prenotazioni/.git" ]]; then
	fail "Zip build: cartella .git inclusa per errore"
else
	pass "Zip build: nessuna cartella .git"
fi

# 8. Updater: hook critici
echo "--- GitHub updater ---"
UPDATER="includes/class-github-updater.php"
for hook in upgrader_source_selection verify_install fix_source_selection; do
	if ! grep -q "$hook" "$UPDATER"; then
		fail "Updater: manca $hook"
	fi
done
if ! grep -q 'cvp_is_install_complete' "$MAIN_FILE"; then
	fail "Manca controllo installazione incompleta nel file principale"
else
	pass "Bootstrap sicuro presente"
fi

if ! grep -q "class_exists.*Elementor" includes/class-elementor-integration.php; then
	fail "Integrazione Elementor senza controlli class_exists"
else
	pass "Integrazione Elementor difensiva"
fi

if grep -q 'fix_install_directory' "$UPDATER" && grep -q 'merge_directory' "$UPDATER"; then
	fail "Updater: logica pericolosa fix_install_directory/merge ancora presente"
fi
pass "GitHub updater: hook di sicurezza presenti"

if ! grep -q 'class Pricing' includes/class-pricing.php; then
	fail "Manca classe Pricing unificata"
else
	pass "Classe Pricing presente"
fi

if ! grep -q 'WP_UNINSTALL_PLUGIN' uninstall.php; then
	fail "uninstall.php non valido"
else
	pass "uninstall.php presente"
fi

echo
if [[ "$ERRORS" -gt 0 ]]; then
	echo "=== RISULTATO: $ERRORS errori — RELEASE BLOCCATA ===" >&2
	exit 1
fi

echo "--- v1.3 feature checks ---"
if bash scripts/test-v13.sh; then
	pass "Test funzionali v1.3.0"
else
	fail "Test funzionali v1.3.0 falliti"
fi

echo
if [[ "$ERRORS" -gt 0 ]]; then
	echo "=== RISULTATO: $ERRORS errori — RELEASE BLOCCATA ===" >&2
	exit 1
fi

echo "=== RISULTATO: tutte le verifiche superate ==="
exit 0
