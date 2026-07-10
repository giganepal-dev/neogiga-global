# FIRST_25_COUNTRY_CONFIGURATION

Seeded by `Database\Seeders\GlobalCommerceMarketplaceSeeder` (idempotent, safe to re-run). No tax
rates, payment credentials, or delivery claims are configured — `currencies.exchange_rate` is the
literal placeholder `1.0` for every currency, not a real rate.

| # | Country | Prefix | Currency | Marketplace code | Launch status | Checkout | Notes |
|---|---|---|---|---|---|---|---|
| 1 | India | `/in` | INR | INDIA | active (pre-existing) | on | own domain neogiga.in |
| 2 | Nepal | `/np` | NPR | NEPAL | active (pre-existing) | on | own domain giganepal.com |
| 3 | Bangladesh | `/bd` | BDT | BANGLADESH | preview | off | |
| 4 | Sri Lanka | `/lk` | LKR | SRILANKA | preview | off | |
| 5 | Pakistan | `/pk` | PKR | PAKISTAN | preview | off | |
| 6 | Bhutan | `/bt` | BTN | BHUTAN | preview | off | |
| 7 | Maldives | `/mv` | MVR | MALDIVES | preview | off | |
| 8 | UAE | `/ae` | AED | UNITEDARABEMIRATES | preview | off | |
| 9 | Saudi Arabia | `/sa` | SAR | SAUDIARABIA | preview | off | |
| 10 | Qatar | `/qa` | QAR | QATAR | preview | off | |
| 11 | Oman | `/om` | OMR | OMAN | preview | off | |
| 12 | Kuwait | `/kw` | KWD | KUWAIT | preview | off | |
| 13 | United States | `/us` | USD | UNITEDSTATES | preview | off | GLOBAL already serves USD globally |
| 14 | Canada | `/ca` | CAD | CANADA | preview | off | |
| 15 | United Kingdom | `/uk` | GBP | UNITEDKINGDOM | preview | off | path is `/uk`; country ISO2 is `GB` (no `/gb` route exists) |
| 16 | Germany | `/de` | EUR | GERMANY | preview | off | |
| 17 | France | `/fr` | EUR | FRANCE | preview | off | |
| 18 | Italy | `/it` | EUR | ITALY | preview | off | |
| 19 | Spain | `/es` | EUR | SPAIN | preview | off | |
| 20 | Netherlands | `/nl` | EUR | NETHERLANDS | preview | off | |
| 21 | Australia | `/au` | AUD | AUSTRALIA | preview | off | |
| 22 | New Zealand | `/nz` | NZD | NEWZEALAND | preview | off | |
| 23 | Brazil | `/br` | BRL | BRAZIL | preview | off | |
| 24 | South Africa | `/za` | ZAR | SOUTHAFRICA | preview | off | |
| 25 | Kenya | `/ke` | KES | KENYA | preview | off | |

GLOBAL itself has no `url_prefix` (root domain) and is the sole `global_fallback=true` marketplace.

## Every preview marketplace also has
`is_active=false`, `redirect_enabled=false`, `local_seller_support=false`,
`local_warehouse_support=false`, `local_payment_support=false`, `allow_vendor_registration=false`,
`tax_rate=0.00` (schema default, no rules entered), `locale='en'`, `default_language='en'`,
`supported_languages=['en']`, and a `description` explicitly stating pricing/stock/sellers/payments
are not yet launched.

## To run
```bash
php artisan db:seed --class=Database\\Seeders\\GlobalCommerceMarketplaceSeeder --force
```
Verified idempotent and safe to re-run on this cycle's local `neogiga_test` database (see
GLOBAL_COMMERCE_VALIDATION_REPORT.md). **Not yet run on production** — see that report for why.
