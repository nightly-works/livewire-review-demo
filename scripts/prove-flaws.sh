#!/usr/bin/env bash
# Runs the security tests against the application code as delivered (tag as-delivered) and checks
# that each of the four findings' tests fails there, i.e. that every attack worked before the fixes.
# Needs a clean tree under the paths below, a full clone (the tag) and `composer install`; the tree is
# put back to HEAD on exit.
set -euo pipefail
cd "$(dirname "$0")/.."

PATHS=(app bootstrap config database routes resources)
git diff --quiet HEAD -- "${PATHS[@]}" || { echo "uncommitted changes under ${PATHS[*]}: commit or stash first"; exit 1; }
added=$(git diff --name-only --diff-filter=A as-delivered HEAD -- "${PATHS[@]}")
removed=$(git diff --name-only --diff-filter=D as-delivered HEAD -- "${PATHS[@]}")
restore() {
  git checkout -q HEAD -- "${PATHS[@]}"
  for f in $removed; do rm -f "$f"; done
  rm -f junit-as-delivered.xml
}
trap restore EXIT

git checkout -q as-delivered -- "${PATHS[@]}"
# Files the fixes added (e.g. a policy) did not exist in the delivered app.
for f in $added; do rm -f "$f"; done

php artisan test --filter=LivewireSecurityReviewTest --log-junit junit-as-delivered.xml >/dev/null 2>&1 || true

# One line per security test: its name and whether it failed (a <failure> or <error> child).
results=$(php -r '
  $xml = simplexml_load_file($argv[1]);
  foreach ($xml->xpath("//testcase") as $case) {
      $failed = count($case->failure) + count($case->error) > 0 ? "failed" : "passed";
      echo $case["name"], " ", $failed, PHP_EOL;
  }' junit-as-delivered.xml)

status=0
for t in test_1_ test_2_ test_3_ test_4_; do
  line=$(grep "^${t}" <<<"$results" || true)
  case "$line" in
    *" failed") echo "as delivered, attack works: ${line% failed}" ;;
    *" passed") echo "as delivered, test passed (attack did not reproduce): ${line% passed}"; status=1 ;;
    *) echo "missing: ${t}"; status=1 ;;
  esac
done
exit $status
