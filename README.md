# Cuba Investment Network — Core Backend Plugin (`cuba-investment-core`)

The **Cuba Investment Network Core** plugin provides the decoupled, production-grade backend architecture for the Cuba Investment Network platform.

## Architectural Highlights

- **Decoupled Business Logic**: Kept 100% independent of the active theme. Runs cleanly across any standard WordPress environment.
- **Role-Based Domain Modeling**:
  - `cin_investor`: Investor accounts with discovery, introduction inquiries, direct messaging, and document access.
  - `cin_business_owner`: Cuban private enterprise owners (MIPYMEs, CNAs, TCPs) with listing submissions, inquiry management, and connection tracking.
- **Custom Relational Database Tables**:
  - `wp_cin_inquiries`: High-integrity introduction inquiries.
  - `wp_cin_connections`: Mutual direct connections between investors and entrepreneurs.
  - `wp_cin_conversations` & `wp_cin_messages`: Dedicated private messaging subsystem with indexed read states and conversation threads.
  - `wp_cin_notifications`: Asynchronous in-app and email alert event log.
  - `wp_cin_subscriptions`: Prepared schema for future subscription tiers (100% free at launch).
  - `wp_cin_audit_logs`: Immutable security and administrative governance audit trail.
- **Launch Entitlement Engine**:
  - Launch version is completely FREE with zero payment gateway dependencies (no Stripe, PayPal, or WooCommerce).
  - Feature quotas and capability checks are managed via `EntitlementService` with filterable providers (`cin_user_entitlements`, `cin_can_perform_feature`) for clean future extensibility.
- **Restricted Document Access Design**:
  - Pitch decks and confidential Cuban business documents are isolated in a protected directory (`wp-content/uploads/cin-protected/`) with `.htaccess` deny rules.
  - Downloads are gated via cryptographically signed, expiring HMAC tokens (`DocumentAccess::generate_token()`).
- **Secure REST API (`/wp-json/cin/v1/`)**:
  - Granular `permission_callback` enforcement, transient-backed rate limiting, nonces, and input sanitization.
