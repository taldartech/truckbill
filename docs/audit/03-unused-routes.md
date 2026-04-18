# Unused Routes Audit

Date: 2026-04-15  
Last updated: 2026-04-18 (section A removed; onboarding + public lead routes unchanged)  
Method: `php artisan route:list --json` + repository-wide static reference scan (`route('name')`, `->route(...)`, direct URI usage patterns).

## Scope and confidence

- Static analysis can identify likely dead routes, but cannot prove zero runtime usage.
- Dynamic clients, external integrations, and manually visited URLs are not fully visible in static scans.
- Use this as a cleanup shortlist with verification steps.

## A) High-confidence likely unused routes

**Status: removed (2026-04-18).** Routes and dedicated controller methods deleted after repo-wide search (no Blade/JS/tests references). **Onboarding and lead-generation routes were not modified** (see section C).

Removed endpoints:

| Method | URI | Route name (if any) | Controller method removed |
|--------|-----|---------------------|---------------------------|
| GET | `/api/freight-bookings/{id}/details` | `api.freight_bookings.details` | `FreightBookingController::getFreightBookingDetails` |
| GET | `/api/references/{transporterId}/{truckId}/lrs` | _(none)_ | `LorryReceiptController::getReferencesForTransporterTruck` |
| GET | `/api/trucks/{transporter}/lrs` | _(none)_ | `TruckController::getTrucksFromLrs` |
| GET | `/api/trucks/{truck}/agency-transporters` | _(none)_ | `TruckController::getAgencyTransporters` |

## B) Candidate routes with no static route-helper references

These were flagged by static scan but may still be used via direct URLs, JS string composition, or external integrations:

- `GET /api/truck-owners` (unnamed)
- `GET /drivers/{driver}/edit` (`drivers.edit`)
- `GET /freight_bookings/data` (`freight_bookings.datatable`)
- `GET /invoices/lrs/{memoIds}` (`invoices.lrs`)
- `POST /invoices/{invoice}/payments` (`invoices.payments.store`)
- `GET /invoices/{invoice}/payments` (`invoices.payments.index`)
- `POST /lorry_receipts/bulk-payment` (`lorry_receipts.bulkPayment`)
- `GET /lorryreceipts/by-linked/{linkedId}` (`lorryreceipts.by_linked`)
- `GET /transporters/{transporter}` (`transporters.show`)
- `GET /truck_owners/{truck_owner}` (`truck_owners.show`)
- `DELETE /truck_owners/{truck_owner}` (`truck_owners.destroy`)
- `GET /trucks/{truck}` (`trucks.show`)
- `PUT /trucks/{truck}/assign-driver` (`trucks.assign-driver`)
- `GET /trucks/{truck}/edit` (`trucks.edit`)
- `PATCH /trucks/{truck}/remove-transporter` (`trucks.remove-transporter`)
- `POST /voucher_payments/bulk-payment` (`voucher_payments.bulkPayment`)

## C) Routes previously suspected but now confirmed in use

- `GET /api/onboarding/token/{token}` (`onboarding.token.validate`) - externally used onboarding flow.
- `POST /api/onboarding/verify-otp` (`onboarding.otp.verify`) - externally used onboarding flow.
- `PUT|PATCH /leads/{lead}` (`leads.update`) - externally used lead management flow.
- `POST /leads/{lead}/reject` (`leads.reject`) - externally used lead management flow.
- `POST /leads/{lead}/status` (`leads.status`) - externally used lead management flow.
- `POST /public/leads` (`leads.website.store.compat`) - externally used public lead capture flow.
- `GET /api/lorry-receipts/{id}/details` - used in `resources/views/lorry_receipts/index.blade.php`.
- `GET /api/trucks/{truck}/owner-transporters` - used in `public/js/lr-entity.js`.
- `GET /api/transporters/{transporter}/trucks` - used in `resources/views/freight_bookings/create.blade.php`.

## D) Safe removal process

For each candidate route:

1. Search server logs for endpoint hits (7-30 days).
2. Run staging smoke tests for related modules.
3. Remove route + controller action together.
4. Re-run route list and feature tests.
5. Keep a rollback patch if runtime traffic reveals missed usage.
