# Salma Tech Automotive Marketplace - Business Rules

## Overview

This document defines all business rules, constraints, workflows, and policies governing the Salma Tech Automotive Marketplace. These rules are the source of truth for:

- Feature requirements
- Validation logic in code
- Approval workflows
- State transitions
- Data constraints
- Compliance requirements

---

## 1. User Roles & Permissions

### Role Hierarchy

```
Super Admin (Salma Tech Owner)
├── Salma Tech Admin
│   ├── Vendor Admin
│   │   ├── Dealers (multiple)
│   │   ├── Suppliers (multiple)
│   │   └── Service Centers (multiple)
│   └── Customer
└── Private Sellers
    └── Customer
```

### Role Definitions & Permissions

#### Super Admin
- Full system access
- User and vendor management
- Financial transaction visibility
- System configuration
- Audit log access

| Permission | Scope |
|-----------|-------|
| Manage all users | All users |
| Manage vendors | All vendors |
| View all transactions | All transactions |
| Configure system settings | Global |
| Access audit logs | Full system |
| Override approvals | All workflows |

#### Salma Tech Admin
- Operational oversight
- Vendor approval and moderation
- Customer dispute resolution
- Financial reporting
- Content moderation

| Permission | Scope |
|-----------|-------|
| Manage vendors | All vendors |
| View transactions | All transactions |
| Moderate listings | All listings |
| Resolve disputes | All disputes |
| Generate reports | All data |
| Delete inappropriate content | All content |

#### Vendor (Dealer/Supplier/Service Center)
- Own inventory management
- Customer communication
- Order fulfillment
- Financial reporting (own)
- Profile management

| Permission | Scope |
|-----------|-------|
| Manage own listings | Own vendor account |
| View own orders | Own orders only |
| Communicate with customers | Own transactions |
| View own financials | Own transactions |
| Modify own profile | Own vendor account |

#### Private Seller
- Minimal permissions
- Personal inventory only
- Single-item listings or small bulk

| Permission | Scope |
|-----------|-------|
| Create limited listings | 50 max lifetime |
| View own orders | Own orders only |
| Communicate with buyers | Own transactions |

#### Customer
- Shopping and review permissions only

| Permission | Scope |
|-----------|-------|
| Browse and search | Public listings |
| Manage wishlist | Own wishlist |
| Purchase | Public listings |
| Leave reviews | Purchased items only |

---

## 2. User Management Rules

### Account Creation & Activation

**Rule U001**: Email must be unique across system
- **Validation**: Check email uniqueness before registration
- **Error**: "Email already registered"
- **Exception**: Admins can reset forgotten emails

**Rule U002**: Password minimum requirements
- **Requirements**: Minimum 10 characters, 1 uppercase, 1 number, 1 special character
- **Validation**: Real-time feedback during registration
- **Storage**: Hashed using bcrypt (min. 12 rounds)

**Rule U003**: Account activation requires email verification
- **Process**:
  1. User registers, receives verification email
  2. Link valid for 24 hours
  3. 3 resend attempts maximum per day
  4. After 7 days, unverified accounts deleted automatically
- **Exception**: Super Admin can manually activate

**Rule U004**: Phone number is optional but can be verified
- **Validation**: E164 format, must start with +263 (Zimbabwe)
- **Verification**: SMS OTP sent, valid for 10 minutes, 3 attempts
- **Use**: Notifications, account recovery, two-factor authentication (future)

### Account Suspension & Termination

**Rule U005**: Account suspension workflow
- **Causes**: Multiple payment failures (3+), abuse reports (5+), regulatory request
- **Process**:
  1. Warning email sent to user
  2. 7-day grace period for remediation
  3. Auto-suspend if unresolved
  4. User notified of suspension reason
- **Recovery**: User can appeal via support

**Rule U006**: Account deletion is permanent
- **Process**: 30-day deletion pending period (user can cancel)
- **Data Handling**:
  - Personal data (name, email, phone) anonymized
  - Transaction history retained for auditing
  - Listings transferred to system account or removed
  - Reviews reassigned to "Deleted User"

**Rule U007**: Super Admin can permanently delete accounts
- **Requires**: Documented reason, 24-hour deletion notice to user
- **Audit**: Logged with Super Admin identity and timestamp

---

## 3. Vendor Management Rules

### Vendor Onboarding & Approval

**Rule V001**: Vendor registration requires role-specific documentation

| Vendor Type | Required Documents | Verification | Approval Time |
|-------------|-------------------|--------------|----------------|
| **Dealer** | Business registration, Tax ID, ID copy, Proof of address, Bank details | KYC verification | 2-5 business days |
| **Parts Supplier** | Business registration, Tax ID, ID copy, Bank details | Manual review | 2-3 business days |
| **Service Center** | Business registration, Tax ID, ID copy, Service capability proof | Manual review | 2-3 business days |
| **Individual Seller** | Government ID, Proof of address, Bank details | Automated ID scan | 24 hours |
| **Salma Tech** | N/A (pre-approved) | N/A | Immediate |

**Rule V002**: Vendor account states

```
Pending → Approved → Active
            ↓
        Rejected (with reason email)

Active ⇄ Suspended (admin action)
  ↓
Closed (vendor request + 7-day notice)
```

- **Pending**: Documentation submitted, awaiting review
- **Approved**: Vendor approved, can activate account
- **Active**: Full marketplace access
- **Suspended**: Admin action, cannot list/sell
- **Closed**: Vendor requested closure, no new transactions

**Rule V003**: Vendor information updates require re-verification
- **Changes Requiring Verification**: Bank account, business registration, Tax ID
- **Process**: Change submitted → Auto-flagged for review → Approved/Rejected within 48 hours
- **Impact**: Earnings held during verification (max 7 days)

**Rule V004**: Bank account must be verified with micro-deposits
- **Process**: Two deposits <$1 each sent to provided account
- **User Action**: Vendor must enter amounts within 10 days
- **Verification**: Automatic confirmation when both amounts match
- **Failure**: After 10 days, must re-enter account details

### Vendor Tiers & Benefits

**Rule V005**: Vendor tier system (future-ready structure)

| Tier | Listings Limit | Commission | Features | Monthly Fee |
|------|----------------|-----------|----------|------------|
| **Bronze** (Free) | 10 | 10% | Basic listing, reviews | $0 |
| **Silver** | 100 | 8% | Featured listings (5/month), analytics | $50 |
| **Gold** | Unlimited | 5% | Featured listings (20/month), analytics, bulk tools | $200 |
| **Platinum** | Unlimited | 3% | All Gold + priority support, API access | $500 |

- **Upgrades**: Effective immediately upon payment
- **Downgrades**: Effective next month
- **Enforcement**: Existing listings grandfathered, new listings limited per tier

### Vendor Metrics & Reputation

**Rule V006**: Seller rating calculation
- **Formula**: (Sum of all order ratings × order value weight) / Total orders
- **Weighted**: Higher-value orders weighted 1.5x in calculation
- **Minimum**: Minimum 5 completed orders before rating displayed
- **Updates**: Real-time, calculated on new review submission

**Rule V007**: Performance monitoring & automatic downgrade
- **Metrics Tracked**:
  - Order cancellation rate (target: <5%)
  - Return rate (target: <10%)
  - Negative review percentage (target: <15%)
  - Response time to messages (target: <4 hours)
- **Consequences**:
  - 30-day warning if thresholds exceeded
  - Automatic demotion to lower tier (30 days)
  - Suspension after 2 demotions in 6 months
  - Reactivation requires 60 days of compliance

**Rule V008**: Vendor can export their data annually
- **Format**: CSV, JSON, PDF
- **Content**: All listings, orders, reviews, financials
- **Frequency**: Once per calendar year, free
- **Timeline**: Generated within 7 days

---

## 4. Product Management Rules

### Product Listing Requirements

**Rule P001**: Product listing mandatory fields

| Field | Type | Validation | Required |
|-------|------|-----------|----------|
| **Title** | String | 10-200 chars, no spam keywords | Yes |
| **Description** | Text | 50-5000 chars, plain text or markdown | Yes |
| **Category** | Select | Must exist and be active | Yes |
| **Price** | Decimal | 0.01-999,999.99 ZWL/USD | Yes |
| **Quantity** | Integer | 0-999,999 units | Yes |
| **Images** | File | JPG/PNG, max 5MB each, 1-10 images | Yes (min 1) |
| **SKU** | String | Unique per vendor, alphanumeric, max 50 chars | No |
| **Condition** | Select | New, Like New, Used, Refurbished | Yes (if product type supports) |
| **Warranty** | String | Warranty duration and terms | No |
| **Shipping** | Select | Standard, Express, Pickup | Yes |

**Rule P002**: Product title rules (anti-spam)
- **Prohibitions**:
  - Repeat words/letters: "AMAZING AMAZING" blocked
  - Excessive punctuation: More than 2 exclamation marks blocked
  - Contact info in title: Email, phone numbers blocked
  - Misleading claims: "Guaranteed money maker", "Get rich quick" blocked
  - Trademark abuse: Luxury brand names only with verification
- **Validation**: Real-time check during listing creation

**Rule P003**: Product pricing rules
- **Minimum**: 0.01 ZWL or equivalent USD
- **Maximum**: 999,999.99 (single listing)
- **Currency**: ZWL or USD, customer sees both (real-time exchange rate)
- **Sales Tax**: Included in price (marketplace handles remittance)
- **Dynamic Pricing**: Permitted if disclosed clearly

**Rule P004**: Product inventory management
- **Quantity**: Tracked in real-time
- **Reservations**: Reserved for 30 minutes during checkout
- **Auto-deactivation**: Listing set to inactive when quantity = 0
- **Re-activation**: Vendor can reactivate when stock available
- **Overselling Prevention**: Checkout blocked if insufficient inventory

### Product Approval & Moderation

**Rule P005**: Product approval workflow
- **Auto-approved categories**: Most products auto-approved on submission
- **Manual review categories**: Vehicles, high-value items (>$10,000), electronics
- **Timeline**: Manual review completed within 24 hours (48 for vehicles)
- **Rejection**: Vendor notified with specific reason, can resubmit after fix
- **Appeals**: Vendor can appeal to admin within 7 days

**Rule P006**: Prohibited products
- **Completely Banned**: Weapons, drugs, fake documents, counterfeit goods, animal parts
- **Restricted**: Electronics requiring certification, hazardous materials, used medical equipment
- **Age-Restricted**: Alcohol (verified 18+), tobacco (verified 18+)
- **Enforcement**: Automated detection + manual review for borderline cases

**Rule P007**: Duplicate listing prevention
- **Detection**: Same SKU, title, and price within same vendor
- **Action**: If duplicate detected within 7 days, flag for review
- **Reason**: Prevent accidental duplicates from inventory syncing
- **Override**: Vendor can intentionally duplicate with different price/description (clear distinction)

### Product Images & Media

**Rule P008**: Product image requirements
- **Format**: JPG or PNG only
- **Size**: Minimum 300×300px, maximum 5MB per image
- **Limit**: Maximum 10 images per listing
- **Processing**: Auto-resize to 800×800px for thumbnail, 1600×1600px for detail view
- **Watermarks**: Allowed but must be minimal (vendor branding acceptable)
- **Inappropriate Content**: Removed by moderation system

**Rule P009**: Image optimization for CDN
- **Automation**: Auto-convert to WebP format where supported
- **Caching**: CDN caching set to 90 days (cache bust on image update)
- **Lazy Loading**: Images load on-demand in product list views
- **Responsive**: Automatic srcset generation for mobile/tablet/desktop

---

## 5. Vehicle Listing Rules

### Vehicle Information Requirements

**Rule VH001**: Vehicle listing mandatory fields

| Field | Type | Validation | Required |
|-------|------|-----------|----------|
| **Year** | Integer | 1950-current year+1 | Yes |
| **Make** | Select | Pre-defined list (100+ makes) | Yes |
| **Model** | String | 50 chars max | Yes |
| **Body Type** | Select | Sedan, SUV, Truck, etc. | Yes |
| **Transmission** | Select | Manual, Automatic, CVT | Yes |
| **Fuel Type** | Select | Petrol, Diesel, Electric, Hybrid | Yes |
| **Engine CC** | Integer | 100-10,000 cc | No |
| **Mileage** | Integer | 0-999,999 km, or "Unknown" | Yes |
| **Color** | Select | Pre-defined list (50+ colors) | Yes |
| **Condition** | Select | New, Used, Salvage, Rebuilt | Yes |
| **VIN** | String | 17 chars, validated format | No (but recommended) |
| **Registration #** | String | 10 chars max | No |
| **Service History** | Text | Free text, 0-1000 chars | No |
| **Features** | Multi-select | Pre-defined features (50+) | No |
| **Photos** | File | Minimum 5 photos, maximum 20 | Yes |

**Rule VH002**: Vehicle pricing rules
- **Base Price**: Required, displayed prominently
- **Financing**: Vendor can offer financing terms (30% down, 24 months @ 15% APR, etc.)
- **Trade-in**: Vendor can accept trade-ins (value negotiated separately)
- **Warranty**: Duration (months/km) and coverage must be specified
- **Hidden Costs**: Must disclose transfer fees, registration costs, inspection fees

**Rule VH003**: Vehicle condition validation
- **New**: Mileage = 0, condition = "New"
- **Used**: Mileage > 0, condition = "Used"
- **Salvage**: Mileage optional, documentation required (salvage title scan)
- **Rebuilt**: Mileage required, inspection report recommended
- **Cross-validation**: System enforces logical mileage/condition combinations

**Rule VH004**: Vehicle inspection & certification (Phase 2+)
- **Future Requirement**: Third-party inspection reports
- **Process**: Vendor arranges inspection, report uploaded, badge awarded
- **Badge Value**: Inspected vehicles displayed with badge, boost search ranking
- **Cost**: Vendor pays inspection fee ($50-100)

### Vehicle Listing Approval

**Rule VH005**: Vehicle approval workflow (strict, manual)
- **Submission**: All vehicles submitted for manual review
- **Timeline**: 48 hours typical, up to 5 business days
- **Reviewer Checks**:
  - Physical images (not stock photos)
  - Realistic mileage for year/condition
  - Pricing reasonableness (vs. market)
  - Honest condition description
  - Complete required fields
- **Rejection Reasons**: Stock photos, unrealistic pricing, missing documentation, condition mismatch
- **Resubmission**: Vendor can fix and resubmit (no resubmission limit)

**Rule VH006**: Vehicle deactivation triggers
- **Sold**: Vendor marks as sold, listing ends
- **Inactivity**: No updates for 60 days → auto-deactivated, vendor notified
- **Complaint**: Safety/fraud concern → suspended pending review
- **Inspection**: Failed inspection → vendor must resubmit
- **Reactivation**: Vendor can reactivate after 30 days (pricing/photos must be updated)

---

## 6. Inventory Management Rules

### Stock Tracking

**Rule I001**: Real-time inventory synchronization
- **Reservation Window**: 30 minutes during checkout
- **Timeout**: After 30 minutes, inventory released back to available
- **Oversell Prevention**: Quantity check performed at order confirmation (not checkout)
- **Notification**: Vendor notified when inventory falls below 10% of historical average

**Rule I002**: Bulk inventory management
- **CSV Upload**: Vendors can bulk import inventory (SKU, quantity, price)
- **Validation**: Dry-run preview before import
- **Failure Handling**: Partial success (row-by-row feedback)
- **Rate Limit**: Max 5,000 rows per import, max 5 imports per day

**Rule I003**: Stock-out and backorder handling
- **Stock-out**: When quantity = 0, listing becomes inactive (not removed)
- **Backorder**: Not permitted initially (Phase 1)
- **Phase 2+**: Backorder allowed if vendor explicitly enables
- **Customer Notification**: Email when item back in stock (if subscribed)

---

## 7. Order Management Rules

### Order Creation & States

**Rule O001**: Order state machine

```
Cart (unconfirmed) → Pending (payment processing)
                       ↓
                   Confirmed (payment received)
                       ↓
                   Processing (vendor preparing)
                       ↓
                   Shipped (in transit)
                       ↓
                   Delivered (customer received)
                       ↓
                   Complete (fulfillment finished)

Payment Failed ← Pending (cancel order, refund)

Returned ← Complete (initiate return, within 30 days)
  ↓
Return Shipped ← (vendor ships return)
  ↓
Return Received ← (marketplace inspects)
  ↓
Refunded ← (refund issued to buyer)
```

**Rule O002**: Order creation requires
- **Cart Exists**: User must have active cart
- **Inventory Available**: Real-time check (may differ from browse-time check)
- **Valid Shipping Address**: Matches customer profile
- **Accepted Payment Method**: Pesepay available for payment
- **No Duplicate Orders**: Same items not ordered within 5 minutes

**Rule O003**: Order modification windows
- **Phase**: Pending/Confirmed only
- **Allowed Changes**: Shipping address (if not shipped), payment method (if not confirmed)
- **Not Allowed**: Item quantity, price (must cancel and reorder)
- **Cancellation**: Permitted before "Shipped" status (100% refund if payment processed)

### Order Fulfillment

**Rule O004**: Vendor fulfillment obligations
- **Confirmation**: Vendor must confirm order within 4 hours or system auto-cancels
- **Processing**: Begin fulfillment within 24 hours
- **Shipping**: Ship within 3 business days (or agreed timeline)
- **Tracking**: Provide tracking number within 12 hours of shipment
- **Communication**: Respond to customer messages within 4 hours (business hours)

**Rule O005**: Shipping options & costs
- **Standard**: 5-7 business days, base cost ($5-15)
- **Express**: 2-3 business days, base cost + premium (50%)
- **Pickup**: Customer pickup at vendor location, free
- **Display**: Shipping cost calculated and displayed before checkout
- **Refund**: Non-refundable unless partial return

**Rule O006**: Delivery confirmation
- **Timeline**: Customer has 48 hours to confirm delivery receipt
- **Auto-confirmation**: Automatic after 5 days if no dispute
- **Signature Waived**: Unless high-value item (>$500)
- **Photo Proof**: Recommended for items >$500 value

### Order Disputes & Returns

**Rule O007**: Return policy
- **Window**: 30 days from delivery confirmation
- **Condition**: Item must be in original condition, unopened (unless defective)
- **Exceptions**: 
  - Electronics: 14 days (due to rapid obsolescence)
  - Custom/Made-to-order: No returns
  - Vehicles: Inspection return only (within 3 days)
- **Defects**: Full return window if defective/DOA

**Rule O008**: Return process workflow

```
Customer Initiates Return (within 30 days)
  ↓
Vendor Reviews & Approves (within 48 hours)
  ↓
Customer Ships Item Back (tracking provided)
  ↓
Vendor Inspects Return (within 5 days of receipt)
  ↓
Approved → Refund Issued (within 2 business days)
       ↓
       Denied (vendor specifies reason, can appeal)
```

**Rule O009**: Dispute escalation
- **Trigger**: Unresolved return, payment issue, quality complaint
- **Process**:
  1. Automated resolution suggestions
  2. Vendor-customer negotiation (7 days)
  3. Admin mediation (7 days)
  4. Binding decision (within 24 hours)
- **Authority**: Admin has final authority
- **Appeals**: Final decision is binding, no further appeals

**Rule O010**: Refund processing
- **Method**: Original payment method (within 3-5 business days)
- **Partial**: Can accept partial refund (negotiated)
- **Chargeback Risk**: Pesepay chargeback claims must be resolved within dispute period

---

## 8. Payment Rules

### Payment Processing

**Rule PAY001**: Pesepay payment integration
- **Supported Methods**:
  - Credit/debit cards (all major networks)
  - Mobile money (Econet, NetOne, Telecel)
  - Bank transfer (next business day settlement)
- **Currencies**: ZWL and USD (real-time conversion)
- **Timeout**: Payment completion required within 15 minutes of checkout
- **Retry**: Failed payment can be retried 3 times within 24 hours

**Rule PAY002**: Commission deduction & payout
- **Deduction**: Commission (5-10%) automatically deducted at order confirmation
- **Payout Timing**: 
  - Gross proceeds held for 3 days (dispute window)
  - Commission deducted after 3 days
  - Net proceeds (commission - fees) transferred to vendor within 5-7 business days
- **Manual Hold**: Can extend to 14 days for high-risk transactions (admin discretion)
- **Minimum Payout**: Vendors must accumulate minimum $50 before payout

**Rule PAY003**: Payment processing fees
- **Pesepay Fee**: 2.5% of transaction (platform absorbs or passes to customer?)
- **Bank Transfer Fee**: $1 per transfer (if <$1,000), $2 per transfer (if >$1,000)
- **Currency Conversion**: Real-time rate + 0.5% markup (platform profit)
- **Transparency**: All fees disclosed before payment confirmation

**Rule PAY004**: Fraud detection & prevention
- **AVS Check**: Address Verification Service enabled
- **CVV Check**: Card Verification Value required
- **3D Secure**: Required for high-risk or premium debit cards
- **Velocity Checks**: Flag multiple purchases from same card within 1 minute
- **Amount Threshold**: Orders >$1,000 flagged for review

**Rule PAY005**: Refund policy
- **Full Refund**: Customer cancels before shipping (minus Pesepay fee, if applicable)
- **Partial Refund**: Return approved but item damaged/missing parts (negotiated)
- **No Refund**: Digital products, downloadable content, services rendered
- **Processing**: Refund initiated within 24 hours, cleared within 3-5 business days

### Escrow & Payment Hold

**Rule PAY006**: Payment escrow model (Pesepay integration)
- **Current Model**: Pesepay holds funds, marketplace directs release
- **Timeline**:
  1. Customer payment received by Pesepay
  2. Marketplace confirms order to Pesepay
  3. Funds held by Pesepay for 3 days (dispute period)
  4. No dispute raised → Pesepay releases to vendor
  5. Dispute raised → Pesepay holds pending resolution
- **Vendor Assurance**: Payment guaranteed if no legitimate dispute

---

## 9. Review & Rating Rules

### Review Submission

**Rule R001**: Review eligibility
- **Requirement**: Purchased item only (verified order)
- **Timing**: After delivery confirmed, before 60 days elapsed
- **Limit**: One review per order item (can update once)
- **Required Fields**:
  - Star rating (1-5)
  - Title (10-100 chars)
  - Comment (10-1000 chars)
  - Quality rating (separate from overall)
  - Shipping rating (separate from overall)

**Rule R002**: Review moderation rules
- **Auto-approved**: Constructive reviews with balanced detail
- **Flagged for Review**: 
  - Multiple bad words/slurs
  - Competitor attack language ("Buy from X instead")
  - All-caps ranting (>3 consecutive caps)
  - Unverifiable claims ("I know the owner...")
- **Removal Triggers**: 
  - Spam (repeated copy-paste)
  - Harassment/personal attacks
  - Promotional links
  - Off-topic content (not about product)
  - Extreme language/slurs

**Rule R003**: Seller response to reviews
- **Window**: 7 days to respond
- **Visibility**: Public response appended to review
- **Tone**: Professional, empathetic, factual only
- **Content**: Can clarify facts, offer resolution, request removal (if policy violation)
- **Not Allowed**: Threats, promises of compensation (suggests rating manipulation)

**Rule R004**: Negative review protection
- **Vendetta Reports**: If same user leaves 3+ negative reviews on same vendor in 1 month, flagged
- **Report Abuse**: Vendor can report suspicious review pattern (admin reviews)
- **Removal Conditions**: Only if confirmed fake, spam, or policy violation
- **Burden of Proof**: Vendor must provide evidence

### Rating Calculation

**Rule R005**: Overall seller rating calculation
- **Formula**: (Sum of (rating × order_value_weight)) / total_weighted_orders
- **Weight**: Higher-value orders weighted 1.5x
- **Minimum**: Minimum 5 completed orders required for public rating
- **Breakdown**: Star ratings (1, 2, 3, 4, 5) shown as percentages
- **Update**: Real-time calculation on each new review

**Rule R006**: Category-specific ratings
- **Product Quality**: 1-5 star rating
- **Shipping Speed**: 1-5 star rating
- **Communication**: 1-5 star rating
- **Overall**: Weighted average of above (33% each)
- **Public Display**: All sub-ratings visible in seller profile

---

## 10. Notification Rules

### Notification Channels & Preferences

**Rule N001**: Notification delivery methods
- **Email**: Primary (all notifications by default)
- **SMS**: Opt-in (transaction alerts, order updates)
- **In-app**: Popup and persistent messages
- **Push**: Mobile app (Phase 2+)
- **Preference Center**: Users control which notifications, frequency, timing

**Rule N002**: Mandatory notifications (no opt-out)
- Security alerts (login from new device, password changed)
- Payment receipts
- Order confirmation
- Delivery confirmation
- Policy violation notices
- Account suspension/termination notices

**Rule N003**: Notification frequency limits
- **Marketing**: Maximum 2 per week (if opted-in)
- **Transactional**: Real-time (no limit)
- **Digest Option**: Daily or weekly digest instead of individual emails

**Rule N004**: Notification unsubscribe
- **One-Click Unsubscribe**: Link in every email
- **Granularity**: Unsubscribe from specific notification type only
- **Re-opt-in**: User can re-enable in preference center
- **Grace Period**: No resubscription within 30 days of unsubscribe

### Specific Notification Types

**Rule N005**: Order-related notifications
| Event | Recipient | Channel | Timing |
|-------|-----------|---------|--------|
| Order confirmed | Vendor + Customer | Email | Immediate |
| Order shipped | Customer + Vendor | Email + SMS | Immediate |
| Delivery confirmed | Vendor + Customer | Email | Immediate |
| Return requested | Vendor | Email | Immediate |
| Return approved | Customer | Email | Within 1 hour |
| Refund processed | Customer | Email + SMS | Immediate |

**Rule N006**: Seller notifications
| Event | Notification | Timing |
|-------|-------------|--------|
| New listing approved | Email | Immediate |
| Listing rejected | Email (with reason) | Immediate |
| Review posted | In-app + Email | Immediate |
| Low inventory | In-app | When <10% threshold |
| Performance dip | Email (weekly) | Every Monday |
| Payout processed | Email | Immediate |
| Pending payout | Email | 5 days before cutoff |

---

## 11. Promotion & Coupon Rules

### Promotions (Phase 2+)

**Rule PROM001**: Promotional campaign types
- **Percentage Discount**: X% off (e.g., 10% off)
- **Fixed Discount**: $X off (e.g., $5 off)
- **BOGO**: Buy One Get One (free or discounted)
- **Free Shipping**: Free shipping on qualifying orders
- **Tiered Discount**: Discount based on order value ($50-99: 5%, $100+: 10%)

**Rule PROM002**: Promotion eligibility rules
- **Minimum Order Value**: Can require minimum spend
- **Category Restriction**: Can limit to specific categories
- **New Customers Only**: Can require first purchase
- **Combination**: Can stack with coupons (if specified)
- **Usage Limit**: Global limit and per-customer limit

**Rule PROM003**: Promotion administration
- **Creator**: Admin or Vendor (for vendor-specific promos)
- **Visibility**: Admin-created promos site-wide; vendor promos on their listings only
- **Schedule**: Start/end date/time, timezone-aware
- **Auto-end**: Automatically disable at end date (no manual action needed)

### Coupons (Phase 2+)

**Rule COUP001**: Coupon code requirements
- **Format**: 6-12 alphanumeric characters, uppercase, no spaces
- **Uniqueness**: Unique across system
- **Auto-generation**: Can generate random codes or custom codes
- **Bulk Generation**: Can generate batches for promotions

**Rule COUP002**: Coupon usage rules
- **Redemption**: Code entered at checkout
- **Validation**: System checks validity, minimum spend, usage limits
- **One-time Use**: Can restrict to single-use per customer
- **Lifetime Limit**: Maximum number of redemptions system-wide
- **Expiration**: Coupons must have expiration date
- **Timezone**: User timezone used for validity check

**Rule COUP003**: Coupon tracking
- **Attribution**: Track which customer used which coupon
- **Analytics**: Report on coupon usage by code, customer segment
- **Fraud Detection**: Flag codes with unusually high redemption rate (potential fraud/sharing)

---

## 12. Search & Filtering Rules

### Search Indexing

**Rule S001**: Full-text search configuration
- **Indexed Fields**: Title, description, category, make (vehicles), color, features
- **Not Indexed**: Email, payment data, phone numbers
- **Update Frequency**: Real-time indexing on listing creation/update
- **Stale Index Recovery**: Weekly full re-index (via queue job)

**Rule S002**: Search result ranking
- **Factors** (in priority order):
  1. Relevance score (title match weighted 3x)
  2. Recency (newer listings ranked higher, 7-day window)
  3. Seller rating (4.5+ stars ranked higher)
  4. Sales velocity (popular items ranked higher)
  5. Price (can sort ascending/descending)
- **Penalty**: Inactive listings, low-rated vendors, spam-flagged listings

**Rule S003**: Search filters
- **Always Available**: Category, price range, condition
- **Product-Specific**: Brand, type, SKU
- **Vehicle-Specific**: Year, make, model, body type, transmission, fuel type, mileage range
- **Dynamic Filters**: Based on category selected (different filters per category)
- **Filter Combination**: AND logic (e.g., Sedan AND 2018+ AND <$20,000)

**Rule S004**: Autocomplete & suggestions
- **Data Source**: Popular search queries, product titles
- **Frequency**: Updated nightly from search logs
- **Personal**: Can include user's previous searches (if logged in)
- **Throttle**: Max 10 suggestions per request

---

## 13. Security & Compliance Rules

### Data Protection

**Rule SEC001**: Sensitive data encryption
- **In Transit**: HTTPS/TLS 1.3 for all connections
- **At Rest**: Encrypted database fields for:
  - Payment tokens (Pesepay)
  - SSN/ID numbers (encrypted, not stored in plain text)
  - Bank account numbers (last 4 digits only stored plaintext)
  - Passwords (bcrypt hash, never plaintext)
- **Key Management**: Use Laravel encrypted keys, rotated quarterly

**Rule SEC002**: PCI DSS compliance
- **Scope**: Not storing full credit card numbers
- **Method**: Pesepay tokenization (marketplace never sees full card)
- **Compliance**: Annual security audit by third party
- **Breach Protocol**: Notify Pesepay within 1 hour, notify customers within 24 hours

**Rule SEC003**: Password & session management
- **Hashing**: bcrypt minimum 12 rounds
- **Session Duration**: 24 hours for web, 30 days for "remember me"
- **Logout**: All sessions invalidated on password change
- **Concurrent Sessions**: Limit to 3 concurrent sessions per user

**Rule SEC004**: Two-factor authentication (Phase 2+)
- **Requirement**: Optional for customers, mandatory for vendors
- **Methods**: Email OTP, SMS OTP, authenticator app
- **Recovery**: Backup codes generated, printed, stored securely
- **Enforcement**: Required after account recovery from security incident

### Access Control

**Rule SEC005**: Role-based access control (RBAC)
- **Enforcement**: Middleware checks roles at route level
- **Granularity**: Permission-based (not just role-based)
- **API**: API endpoints require explicit permission (no implicit inheritance)
- **Audit**: All permission changes logged with timestamp and actor

**Rule SEC006**: Admin panel access
- **IP Whitelisting**: Optional security feature (configurable)
- **Lockout**: After 5 failed login attempts, account locked for 30 minutes
- **Session Timeout**: 15 minutes of inactivity = auto-logout
- **Activity Logging**: All admin actions logged (create, read, update, delete)

### Fraud Prevention

**Rule SEC007**: Fraud detection rules
- **Rules Engine**: Multiple rules evaluated per transaction
  1. **Velocity Check**: More than 5 orders from same IP in 1 hour = flag
  2. **Geographic Mismatch**: Shipping address >1000km from billing address = flag
  3. **Card Testing**: Multiple failed transactions on same card = flag/block
  4. **Amount Spike**: Order >$500 from account with previous max $100 = flag
  5. **New Account**: Account created <24 hours ago, first large purchase = flag
- **Action**: Flag for manual review, require additional verification, or block

**Rule SEC008**: Dispute & chargeback handling
- **Pesepay Chargeback**: Marketplace informs vendor, attempts resolution
- **Customer Dispute**: Buyer disputes charge with bank
- **Response**: Evidence provided to Pesepay (order confirmation, delivery proof)
- **Outcome**: Win/loss tracked per vendor (pattern indicates fraud)
- **Repeat Offender**: Vendor with 3+ chargebacks in 6 months = suspension

### Compliance

**Rule SEC009**: Regulatory compliance
- **GDPR-Ready**: Data export, deletion, portability supported
- **Zimbabwean Tax**: Sales tax calculated (if applicable), vendor responsible for remittance
- **Consumer Protection**: Returns policy, dispute resolution, refund window all per local law
- **Data Retention**: Customer data retained for 7 years (for tax/audit), deleted thereafter
- **Vendor Agreement**: Legal agreement signed before account activation (electronic)

---

## 14. Moderation & Abuse Prevention

### Content Moderation

**Rule MOD001**: Automated moderation triggers
| Content | Check | Action |
|---------|-------|--------|
| Profanity | Language filter (>20 bad words) | Blur/quarantine |
| Spam | Link density, repeated keywords | Hide, flag for review |
| Contact Info | Email/phone in listing/review | Hide, flag vendor |
| Hate Speech | Slur detection library | Remove, warn vendor |
| Sexual Content | Image analysis + keywords | Remove, warn vendor |

**Rule MOD002**: Manual moderation workflow
- **Reviewer Access**: Admins can view flagged content
- **Verdict Options**: Approve, Remove, Warn Vendor
- **Reason Documentation**: All removals must have documented reason
- **Appeal Process**: Vendor can appeal within 7 days (re-review by different admin)
- **Pattern Analysis**: Multiple removals indicate potential systematic abuse

**Rule MOD003**: Vendor warning system
- **Warning Levels**:
  - Level 1 (First offense): Warning email, content removed
  - Level 2 (2nd offense within 6 months): Content removed, feature temporarily disabled
  - Level 3 (3rd offense): Temporary suspension (7 days) + training required
  - Level 4 (4th offense): Permanent account closure
- **Escalation**: Each level documented, can be reviewed

### Abuse Reporting

**Rule MOD004**: Customer abuse reporting
- **Report Types**: Inappropriate content, scam, spam, safety concern
- **Required Fields**: Specific reason, optional description
- **Anonymous**: Can report anonymously (no account required)
- **Response**: Acknowledgment email (if logged in), resolution update within 48 hours
- **Feedback**: Reporter can rate satisfaction with resolution

---

## 15. Audit & Logging Rules

### System Audit

**Rule AUD001**: Audit log requirements
- **Events Captured**:
  - User authentication (login, logout, failed attempts)
  - Data modifications (create, update, delete on critical data)
  - Permission changes (role assignments, access grants)
  - Payment events (transactions, refunds, payouts)
  - Content moderation actions
  - Admin overrides
- **Data Logged**: Timestamp, actor (user ID), action, affected resource, old/new values
- **Retention**: 7 years (for compliance)
- **Immutability**: Audit logs cannot be modified (append-only)

**Rule AUD002**: Audit log access
- **Viewers**: Super Admin only
- **Search**: Filterable by date range, actor, action, resource
- **Export**: Can export to CSV for external audit
- **Monitoring**: Alerts on suspicious patterns (e.g., multiple failed logins)

**Rule AUD003**: Data export for users
- **Right**: Every user can export their data
- **Scope**: All personal data (profile, transactions, reviews, messages)
- **Format**: JSON or CSV
- **Timeline**: Generated within 7 days, link valid for 30 days
- **Frequency**: Maximum once per year (free), additional exports ($50 fee)

---

## 16. Reporting & Analytics Rules

### Business Reporting

**Rule RPT001**: Admin dashboard metrics
- **Real-time Metrics**: Active users, GMV (Gross Merchandise Value), orders today
- **Daily Reports**: New users, new sellers, transaction count, commission earned
- **Weekly Reports**: Top products, top sellers, refund rate, fraud incidents
- **Monthly Reports**: Trend analysis, growth rates, revenue breakdown

**Rule RPT002**: Vendor analytics
- **Visibility**: Vendors can access their own analytics only
- **Metrics**: Orders, revenue, refund rate, customer reviews, search traffic
- **Trends**: Charts showing 30/90-day trends
- **Competitor Benchmarking**: Industry average comparison (anonymized)
- **Data Export**: Vendors can export analytics monthly

**Rule RPT003**: Customer analytics (Privacy-first)
- **Anonymous**: Aggregated metrics only (no personal behavior tracking)
- **Metrics**: Popular searches, trending categories, seasonal patterns
- **Restriction**: Cannot track individual user journey across sessions

---

## 17. Special Policies

### Seasonal & Holiday Rules

**Rule SP001**: High-volume handling
- **Black Friday / Cyber Monday**: 
  - Capacity planning (3x normal load expected)
  - Automatic rate limiting if queue depth exceeds threshold
  - Vendor surge pricing allowed (if disclosed)
- **Year-End**: Expected decline in orders post-December 15
- **January**: New year promotion push expected

### Dispute Resolution Authority

**Rule SP002**: Final decision authority
- **Customer Support**: Resolves routine issues (shipping, missing items, simple returns)
- **Vendor Support**: Vendor relations, onboarding, technical support
- **Payment Disputes**: Pesepay arbiter (between customer and vendor)
- **Content/Moderation**: Admin (final authority on policy violations)
- **Appeals**: Super Admin reviews appeals only

---

## Appendix: Validation Rules Reference

### Email Validation
```
Regex: ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$
```

### Password Requirements
```
- Minimum 10 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- At least 1 special character (!@#$%^&*)
```

### Phone Number Format (Zimbabwe)
```
E.164 Format: +263XXXXXXXXX (11 digits)
Accepted Prefixes: +263-7xx (Econet), +263-7xx (NetOne), +263-7xx (Telecel)
```

### VIN Validation
```
Format: 17 alphanumeric characters
Check digit: Position 10 (digit only)
No characters: I, O, Q
```

---

*Document Version: 1.0*  
*Last Updated: 2026*  
*Status: Approved*
