# Unused Routes Audit

Date: 2026-04-15  
Method: `php artisan route:list --json` + repository-wide static reference scan (`route('name')`, `->route(...)`, direct URI usage patterns).

## Scope and confidence

- Static analysis can identify likely dead routes, but cannot prove zero runtime usage.
- Dynamic clients, external integrations, and manually visited URLs are not fully visible in static scans.
- Use this as a cleanup shortlist with verification steps.

## A) High-confidence likely unused routes

These currently have no static references in repository scans and no manual hit during cross-check:

1. `GET /api/freight-bookings/{id}/details` (`api.freight_bookings.details`)
2. `GET /api/references/{transporterId}/{truckId}/lrs` (unnamed)
3. `GET /api/trucks/{transporter}/lrs` (unnamed)
4. `GET /api/trucks/{truck}/agency-transporters` (unnamed)

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
