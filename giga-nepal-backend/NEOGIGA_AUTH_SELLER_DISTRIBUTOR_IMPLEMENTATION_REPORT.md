# NeoGiga Auth Seller Distributor Implementation Report

Date: 2026-07-08

## Completed

- Added exact non-versioned customer auth APIs under `/api/auth/*`.
- Added seller registration/login/logout/me under `/api/seller/*` and `/api/v1/seller/*`.
- Added distributor registration/login/logout/me under `/api/distributor/*` and `/api/v1/distributor/*`.
- Added form request validation for customer, seller, distributor registration, and login.
- Added clean `UserResource`, `SellerResource`, and `DistributorResource`.
- Added shared token issuing/auth service using the existing `api_token_hash` auth system.
- Added seller/distributor registration services that create pending vendor/distributor records only.

## Security

- Passwords are hashed by the existing `User` model cast.
- Login/register routes are throttled.
- Seller/distributor `me` and `logout` routes require `api.token` plus seller/distributor permission.
- Seller/distributor registration creates pending onboarding records only.
- Suspended/rejected seller/distributor accounts cannot log in through portal auth.

## Not Changed

- No Sanctum installation.
- No `.env` changes.
- Existing `/api/v1/auth/*` controller remains active for compatibility.
- Existing seller/distributor panel route groups remain protected.
