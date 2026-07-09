# NeoGiga Distributor Registration Login Flow

Current foundation:

- Public distributor interest: `POST /api/distributor-applications` and `/api/v1/distributor-applications`
- Distributor application: `POST /api/v1/distributors/apply`
- Distributor panel: `/api/v1/distributor/*` protected by token and distributor permissions

Distributor records remain pending until admin approval. Territory assignment is handled through admin distributor APIs.
