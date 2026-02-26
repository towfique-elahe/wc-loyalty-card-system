# WooCommerce Loyalty Card System

A complete loyalty program plugin for WooCommerce — featuring points earning, tiered memberships, physical-style loyalty cards, and gift cards, all integrated directly into the WooCommerce checkout and My Account pages.

---

## Requirements

| Requirement | Minimum Version |
|---|---|
| WordPress | 5.0 |
| PHP | 7.2 |
| WooCommerce | 4.0 |
| MySQL | 5.6 |

---

## Features

### Loyalty Points
- Customers automatically earn points when an order is marked **Completed**
- Dual earning rate: **1 point per 100 TK** + **5 bonus points per 450 TK** spent
- Fixed point value: **1 point = 1 TK** discount
- Configurable minimum points required before redemption
- Configurable point expiry in days (set to 0 for no expiry)
- Full points history tracked per user
- Points redeemable as a discount via a dedicated WooCommerce payment gateway

### Tiered Membership
- Users are automatically placed into tiers based on their **lifetime points**
- Four default tiers (fully customizable):

| Tier | Points Required | Discount |
|---|---|---|
| Bronze | 0 – 499 | 0% |
| Silver | 500 – 1,999 | 5% |
| Gold | 2,000 – 4,999 | 10% |
| Platinum | 5,000+ | 15% |

- Admin can add, edit, or remove tiers, set custom colors, and preview badges live in the admin panel
- Tier discount auto-applied to cart at checkout

### Loyalty Cards
Three distinct physical-style card types, each with a unique card number, validity period (1 year), and discount rate:

| Card | Issuance | Default Discount |
|---|---|---|
| **Privilege Card** (`PC…`) | Customer purchase or free on qualifying order | 10% |
| **Investor Card** (`IC…`) | Admin-issued only | 20% |
| **Platinum Card** (`PLC…`) | Admin-issued only | 20% |

- Privilege card can be **purchased** (configurable price, default: 500 TK)
- Privilege card is **automatically awarded free** when an order meets the threshold (default: 2,000 TK)
- The customer's **best active card discount** is auto-applied at checkout
- Admin can update card status (Active / Inactive / Expired) from the Loyalty Cards admin page
- Admin can manually issue Investor or Platinum cards to any user

### Gift Cards
- Admin can create gift cards with any custom amount
- Default card denominations: 500, 1,000, 2,000, and 5,000 TK
- Unique auto-generated card numbers
- Track initial amount, current balance, status, and optional expiry date
- Redeemable at checkout via a dedicated WooCommerce gift card payment gateway
- Admin can delete cards from the admin panel

### WooCommerce Payment Gateways
Two custom gateways added to WooCommerce checkout:
- **Points Redemption Gateway** — lets customers spend their points balance
- **Gift Card Gateway** — lets customers redeem a gift card by code

### My Account Integration
Three custom endpoints added to the WooCommerce My Account area:
- `/loyalty-points` — points balance, tier status, and full transaction history
- `/loyalty-cards` — all issued loyalty cards with details
- `/gift-cards` — gift card balances

A loyalty dashboard widget is also shown on the main My Account dashboard, displaying current points balance, tier, and tier discount.

### Admin Reports
Filterable by custom date range. Reports include:
- Total points earned and redeemed in the period
- Points redemption rate (%)
- New gift cards issued
- New loyalty cards issued
- Points distribution breakdown by tier (users per tier, total & average points)
- Daily points activity table (earned vs redeemed per day)

### HPOS Compatible
Fully compatible with WooCommerce **High Performance Order Storage (HPOS)** — declared via `FeaturesUtil::declare_compatibility()`.

---

## Admin Pages

The plugin adds a **Loyalty System** top-level menu in the WordPress admin with six sub-pages:

| Page | Description |
|---|---|
| Dashboard | Overview stats + recent transactions |
| Points Settings | Earning rates, minimum redemption, expiry |
| Gift Cards | Create, view, and delete gift cards |
| Loyalty Cards | View all cards, update status, issue special cards |
| Tiers | Configure tier thresholds, discounts, and colors |
| Reports | Date-range filtered loyalty analytics |

---

## Database Tables

The plugin creates 7 custom tables on activation (prefixed with your WordPress table prefix):

| Table | Purpose |
|---|---|
| `loyalty_points` | User points balance and lifetime points |
| `points_transactions` | Full earn/redeem transaction log |
| `gift_cards` | Gift card records (number, balance, status, expiry) |
| `gift_card_transactions` | Gift card usage per order |
| `loyalty_cards` | Issued loyalty cards (type, discount, validity) |
| `card_purchases` | Record of card purchases linked to orders |
| `loyalty_tiers` | Tier configuration (name, points range, discount) |

Tables are automatically verified and recreated if missing on each plugin load (checked once per day via transient).

---

## Installation

1. Upload the `wc-loyalty-card-system` folder to `/wp-content/plugins/`
2. Make sure **WooCommerce is installed and activated** — the plugin will not activate without it
3. Activate the plugin through **Plugins > Installed Plugins**
4. Navigate to **Loyalty System > Dashboard** to get started

> **Note:** WooCommerce must be active before activating this plugin. Attempting to activate without WooCommerce will show an error and automatically deactivate the plugin.

---

## Configuration

### Points Settings
Go to **Loyalty System > Points Settings**:
- Set how many points are earned per 100 TK
- Set bonus points earned per 450 TK
- Set minimum points required for redemption
- Set point expiry in days (0 = never expires)

### Loyalty Cards
Go to **Loyalty System > Loyalty Cards** to:
- View all issued cards
- Update card status
- Set Privilege Card price and free-card order threshold (via Points Settings)
- Issue Investor or Platinum cards manually to any user

### Tiers
Go to **Loyalty System > Tiers** to:
- Add, edit, or remove tiers
- Set point thresholds, discount percentages, and display colors
- Preview tier badges in real-time before saving

---

## File Structure

```
wc-loyalty-card-system/
├── wc-loyalty-card-system.php       # Main plugin file, bootstrap
├── admin/
│   ├── admin-menu.php               # Admin menu registration & page callbacks
│   ├── admin-ajax-handlers.php      # AJAX action handlers
│   ├── css/
│   │   └── admin-style.css
│   ├── js/
│   │   └── admin-script.js
│   └── partials/
│       ├── dashboard.php
│       ├── points-settings.php
│       ├── gift-cards.php
│       ├── loyalty-cards.php
│       ├── tiers.php
│       └── reports.php
├── includes/
│   ├── class-database.php           # DB query abstraction layer
│   ├── class-loyalty-points.php     # Points earning, redemption, history
│   ├── class-gift-cards.php         # Gift card creation and validation
│   ├── class-privilege-cards.php    # Loyalty card issuance and discount logic
│   ├── class-tier-management.php    # Tier assignment and benefits summary
│   ├── class-admin-settings.php     # Settings registration
│   ├── class-frontend-display.php   # Frontend shortcodes / display
│   └── gateways/
│       ├── class-points-gateway.php # Points redemption payment gateway
│       └── class-gift-card-gateway.php # Gift card payment gateway
├── public/
│   ├── css/
│   │   └── loyalty-system.css
│   ├── js/
│   │   └── loyalty-system.js
│   └── templates/
│       └── loyalty-points.php       # My Account points page template
├── db/
│   └── schema.php                   # Table creation via dbDelta
└── languages/                       # Translation-ready (.pot files)
```

---

## Developer Notes

- The main class `WC_Loyalty_Card_System` uses a **singleton pattern** (`get_instance()`)
- All DB operations go through `Loyalty_DB` (class-database.php) — never raw `$wpdb` calls in business logic
- The plugin is **translation-ready** — text domain: `wc-loyalty-system`
- Admin scripts are only enqueued on pages with `wcls` in the hook name
- The plugin declares WooCommerce HPOS compatibility via `before_woocommerce_init`

---

## Author

**Towfique Elahe**
[towfiqueelahe.com](https://towfiqueelahe.com/)

---

## License

GPL v2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
