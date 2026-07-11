# Country Pricing Engine

Base currency: USD.

Marketplace price formula:

`base_cost_usd -> exchange_rate -> import_duty -> VAT/GST -> warehouse_cost -> freight -> seller_margin -> marketplace_margin -> round_rule`

Existing services already include exchange rate, duty, pricing context and pricing rules. Next phase should connect locale prefix context directly into pricing display and checkout.

