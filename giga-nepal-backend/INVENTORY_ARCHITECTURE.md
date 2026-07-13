# Inventory Architecture

## Authoritative records

`inventory_stocks` is the sellable-stock record. It is keyed by product (and
nullable variant), warehouse, marketplace and vendor where those dimensions
apply. `quantity_available` is the checkout-facing quantity and is maintained
transactionally by `StockMovementService` and `ReservationService`.

`inventory_movements` is the immutable ledger. Every adjustment, transfer,
reservation, release and receiving action records before/after quantities,
reference data, actor context and timestamps. `inventory_reservations` holds
active allocations until checkout commits or releases them.

## Resolution

`RegionStockService` returns filtered public inventory summaries.
`RegionalCommerceService` selects a fulfilment route using active stock plus
warehouse and marketplace assignment. It reports a fulfilment route instead of
claiming that global stock is immediately local. `StockMovementService` uses
database transactions and row locks for inventory mutation.

## Operating rules

- Available stock must never be decremented outside the movement/reservation
  services.
- A product without variants uses a `NULL` variant inventory row; a variant
  must use its own row.
- A warehouse must be active and assigned to the marketplace before it can be
  selected for fulfilment.
- `quantity_on_hand`, reservations and available stock need a periodic audit;
  historical direct imports may pre-date the service layer.

## Remaining work

The existing schema needs a single audited availability facade for all public
product, cart and search reads. That consolidation must be introduced with
compatibility tests before removing any legacy stock reads.
