# Audit Documents Index

Date: 2026-04-15  
Project: `truckbill`

This folder contains the split version of the original consolidated audit.

## Documents

- `docs/audit/01-security-risks.md` - Authorization/data-exposure findings and mitigations.
- `docs/audit/02-unused-code.md` - Unused/unwanted code inventory (controllers, views, assets, debug leftovers).
- `docs/audit/03-unused-routes.md` - Unused route analysis (confirmed and candidate lists).
- `docs/audit/04-tooling-and-architecture.md` - Tooling/build/config issues and cleanup direction.

## Important Notes

- Route usage status is based on static repository scanning.
- Dynamic runtime usage (external clients, bookmarked URLs, dynamic JS composition) can produce false negatives.
- For route removals, always verify with runtime access logs or staging smoke tests first.
