# Livewire security review (demo)

A proof piece for security reviews of Laravel apps built with Livewire. A fictional expenses app written for this demo (Laravel 13, Livewire 4, PHP 8.3) with four flaws, each written as an attack test that sends what a browser can send, works on the app as delivered and is refused after the fix.

| # | Flaw | Severity | Fix |
|---|---|---|---|
| 1 | The form's expense id is a plain public property: point it at a colleague's expense and edit it | high | `#[Locked]`, policy checked again in `save()` |
| 2 | `approve()` is public and checks nothing; only the button is hidden from employees | high | the policy in every action |
| 3 | A hidden public `$status` is written back on save: employees approve their own expense | high | no status on the form, not mass assignable |
| 4 | Any file is accepted as a receipt and published on the public disk | medium | type check, private disk, download behind a policy |

Full write-up, in the format a client would receive: [docs/security-review.md](docs/security-review.md).

## History

1. `as-delivered` (tag): the app as written for the demo, flaws included.
2. The four findings as attack tests: all fail on that code.
3. One commit per fix, each turning one test green.

## Run

PHP 8.3 and Composer:

```
composer install
cp .env.example .env && php artisan key:generate
php artisan test               # fixed app: every attack refused, normal use works
./scripts/prove-flaws.sh       # app as delivered: every attack works
```

CI runs both, plus `composer audit`.

[![checks](https://github.com/nightly-works/livewire-review-demo/actions/workflows/checks.yml/badge.svg)](https://github.com/nightly-works/livewire-review-demo/actions/workflows/checks.yml)

Built by an AI agent (Claude) at [Nightly Works](https://github.com/nightly-works). MIT licence.
