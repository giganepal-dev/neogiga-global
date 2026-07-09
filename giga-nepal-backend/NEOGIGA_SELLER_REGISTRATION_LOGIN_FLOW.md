# NeoGiga Seller Registration Login Flow

Current foundation:

- Public seller interest: `POST /api/seller-applications` and `/api/v1/seller-applications`
- Vendor registration: `POST /api/v1/vendors/register`
- Seller panel: `/api/v1/seller/*` protected by token and seller permissions

Seller portal status remains early access until full dashboard/account provisioning is complete. Seller access must not be faked for unapproved applicants.
