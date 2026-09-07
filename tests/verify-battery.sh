#!/bin/bash
# Tri-state theme pill - verification battery runner.
#
# Usage: tests/verify-battery.sh <theme-dir> [scratch-dir]
#   <theme-dir>    the theme tree under test (repo copy, deployed copy,
#                  or fresh clone - the battery is tree-parametric)
#   [scratch-dir]  where extraction artifacts go (default: mktemp -d)
#
# Proves, against the REAL engine + REAL partial of the given tree:
#   - Part A parse-time resolution (dark/light/system + garbage-key fallback)
#   - Pill syncs to the REAL stored choice (finance v1.12.70 fix)
#   - Click semantics (override persists; system REMOVES the key)
#   - OS live-follow ONLY while on system (explicit choices hold)
#   - Keyboard cycling (arrows / Home / End + preventDefault)
#   - Private-mode (localStorage throws) still resolves + never fatals
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
THEME="${1:-$(dirname "$HERE")}"
SCRATCH="${2:-$(mktemp -d)}"

[ -f "$THEME/inc/darkmode.php" ] || { echo "No inc/darkmode.php in $THEME" >&2; exit 2; }
[ -f "$THEME/template-parts/ui/theme-pill.php" ] || { echo "No theme-pill partial in $THEME (pre-v1.11.0 tree?)" >&2; exit 2; }

mkdir -p "$SCRATCH"

# Render the REAL partial through the WP escape shims.
php -r '
define("ABSPATH", "/tmp/");
function esc_attr_e($t,$d=null){echo htmlspecialchars((string)$t,ENT_QUOTES,"UTF-8",false);}
function esc_html_e($t,$d=null){echo htmlspecialchars((string)$t,ENT_QUOTES,"UTF-8",false);}
ob_start(); require $argv[1]."/template-parts/ui/theme-pill.php";
file_put_contents($argv[2]."/pill-markup.html", ob_get_clean());
' "$THEME" "$SCRATCH"

# Extract the REAL inline engine from inc/darkmode.php.
python3 - "$THEME" "$SCRATCH" <<'PY'
import re, sys
from pathlib import Path
theme, scratch = Path(sys.argv[1]), Path(sys.argv[2])
php = (theme / 'inc/darkmode.php').read_text()
m = re.search(r'<script>\n(.*?)\n\t</script>', php, re.S)
assert m, 'engine <script> not found in inc/darkmode.php'
(scratch / 'engine-extracted.js').write_text(m.group(1))
print('engine extracted:', len(m.group(1)), 'bytes')
PY

SRC_DIR="$SCRATCH" node "$HERE/test-pill.js"
