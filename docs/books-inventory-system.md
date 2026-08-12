# Books Inventory & Distribution System — Design and Implementation Plan

> Module namespace: **Bookstore**. Mounted at `/bookstore`, route names `bookstore.*`,
> controllers in `app/Http/Controllers/Bookstore/`, routes in `routes/bookstore.php`.

---

## 1. What this system is (and what it is not)

SITS already has a **Library ILS** (`app/Models/Book`, `BookCopy`, `ShelfBox`, `Loan`,
`Hold`, `Fine`). That module is *item-level lending*: one physical accessioned copy,
barcoded, checked out to one patron and returned.

This module is a different business entirely, and mixing the two would corrupt both:

| | Library ILS (existing) | Bookstore (this module) |
|---|---|---|
| Unit of tracking | One physical copy, individually accessioned | **Quantity** of an identical printed title |
| Origin | Purchased / donated | **Printed in bulk** by the seminary |
| Movement | Checkout → Return (same copy) | **Issue** to a centre/campus, consumed by students |
| Location | Campus → Floor → Row → ShelfBox | **Store room → Shelf → Shelf section** |
| Money | Overdue fines | **Purchase cost + distribution payment** (CRV, bank ref) |
| Approval | Librarian discretion | **4-stage request workflow** with finance gate |

So: **new tables, new models, new namespace, shared `Campus`, `Course`, `Program`,
`User`, `Document` and the shared `HasTrackingHash` QR concern.** No changes to ILS
tables. The two modules meet only in the navigation and in the shared `campuses` table.

### 1.1 Source of truth — the paper forms

The design is a direct digitisation of the four forms currently in use. Every field on
paper has a column in the schema, so a clerk can move over without losing information.

**Form A — `የመጽሃፍት መጠየቂያ ቅጽ` (Book Request Form)** → `book_requests` + `book_request_items`
+ `book_request_approvals`
- Header: centre name (`ማእከል ስም`), number of students (`የተማሪዎች ብዛት`), requester name
  (`የአስተባባሪ ስም`), mobile, signature, date.
- Lines: №, book / course name, receipt number (`ደረሰኝ ቁጥር`), quantity requested,
  date requested, signature, remark.
- Four signature blocks at the foot — these are literally the workflow stages:
  1. `የርቀት ትምህርት አስተባባሪ` — Distance Education Coordinator confirms the books/quantities.
  2. `ያረጋገጠ ኦፕሬሽን ማናጀር` — Operations Manager verifies availability & genuineness.
  3. `ገንዘብ ክፍል` — Finance confirms the receiver is clear of debt / payment settled.
  4. `ንብረት ክፍል ኃላፊ` — Store (Property) Head releases the stock.

**Form B — `የመጽሃፍ መመዝገቢያ ቅጽ` (Book Registration / Issue-and-Return Form)** →
`book_dispatches` + `book_dispatch_items` + `book_returns`
- Header: centre, student count, coordinator + mobile + signature.
- Lines: №, book/course name, `የመደር ደረሰኝ ቁጥር`, **quantity issued**, date issued,
  signature, **quantity returned**, date returned, **quantity not returned**,
  coordinator signature, property-head signature for the unreturned balance.
- Foot: total (`ድምር` = 90 in the sample), remark, coordinator name/sign/date,
  dispatcher (`ያስረከበ`) name/sign/date.

This is the reconciliation document: **issued − returned = outstanding**, and the
outstanding balance is what the centre owes for. The system must show this per centre,
per title, at all times.

**Form C — `ንብረት መረካከቢያ ፎርም` (Property Handover Form)** → the printable
`BookDispatch` waybill (`resources/views/bookstore/print/dispatch-note.blade.php`).
Columns: item name, code, receiving department, date & time received, quantity,
signature; two signature blocks — handed over by / received by.

**Form D — `SBCE STORE LOG` (bin card)** → `stock_movements`
Columns: No, Date, Description, Unit, Unit Price, Total Price, **Quantity Received**,
**Quantity Issued**, **Remaining Balance**, Remark. This is a classic bin card and it
maps 1:1 onto an append-only ledger with a `balance_after` column. The pink card is
per-title-per-store; our ledger is per-title-per-location and can be *printed as* the
bin card so the store keeper recognises it.

**Shelf label in the photos** — `Sine-Mahiberesb / SM-02 / 26` — is exactly
`title name / shelf-section code / quantity`. That handwritten sticky note becomes the
generated QR label.

---

## 2. Domain model

```
Program ─┐
Course  ─┼─► BookTitle ──< PrintRun            (stock in: printing batches)
Language─┤        │
StudyMode┘        ├──< BookStock >── ShelfSection ── Shelf ── StoreRoom ── Campus
                  │        (quantity on hand per exact location)
                  │
                  ├──< StockMovement            (append-only bin-card ledger)
                  │
                  └──< BookRequestItem >── BookRequest ──< BookRequestApproval
                                                │
                                                ├──< BookPayment (CRV + bank ref + receipt image)
                                                │
                                                └──< BookDispatch ──< BookDispatchItem
                                                             │
                                                             └──< BookReturn ──< BookReturnItem

StockAudit ──< StockAuditLine        (physical count vs system, variance, adjustment)
Center                               (distribution centre + coordinator)
```

### 2.1 Tables

#### `study_modes` — dynamic, per the requirement "or any other dynamically set"
| column | type | notes |
|---|---|---|
| id | id | |
| name | string | "Regular", "Distance", "Evening", "Online" |
| code | string, unique | `REG`, `DST` — used in the book code |
| description | text null | |
| is_active | bool | |
| sort_order | int | |

`Program` (existing) and `Language` (existing enum) supply the other two category axes.
A book's *category* is therefore the triple **(program, language, study mode)** — not a
free-text field. Reports pivot on it.

#### `book_titles` — the master printed book
| column | type | notes |
|---|---|---|
| id | id | |
| code | string, unique | e.g. `SM-02`; generated `{PROGRAM}-{SEQ}` if blank |
| title | string | `ስነ-ማህበረሰብ` |
| subtitle | string null | |
| description | text null | |
| author | string null | |
| edition | string null | |
| isbn | string null | |
| course_id | FK courses null | |
| course_code | string null | denormalised, survives course edits |
| course_name | string null | denormalised |
| program_id | FK programs null | category axis 1 |
| language | string | `Language` enum — category axis 2 |
| study_mode_id | FK study_modes null | category axis 3 |
| page_count | int null | |
| unit_price | decimal(12,2) | selling price to centres/students |
| unit_cost | decimal(12,2) null | rolling average print cost |
| reorder_level | int, default 0 | **low-stock threshold** |
| reorder_quantity | int null | suggested reprint size |
| cover_path | string null | |
| tracking_hash | uuid, unique | QR payload (`HasTrackingHash`) |
| is_active | bool | |
| notes | text null | |
| timestamps, softDeletes | | |

Indexes: `(program_id, study_mode_id, language)`, `code`, `tracking_hash`, `course_id`.
Scout-searchable (`book_titles_index`) so the existing Meilisearch setup covers it.

#### `store_rooms` / `store_shelves` / `shelf_sections` — the QR-based location tree
```
StoreRoom (campus_id, name, code, location_note, manager_id, is_active, tracking_hash)
  └─ Shelf (store_room_id, code, label, capacity, sort_order, tracking_hash)
       └─ ShelfSection (shelf_id, code, name, capacity, sort_order, tracking_hash)
```
Every one of the three levels carries a `tracking_hash` and therefore a printable QR.
A section's **full path** — `Main Store › Shelf A › SM-02` — is what appears under the
QR on the label and in every stock screen. `ShelfSection` is the *only* level that
holds stock; the parents aggregate.

Deliberately **separate** from the ILS `Floor → Row → ShelfBox` tree: that tree models a
reading-room; this one models a warehouse. Reusing it would put print stock inside the
lending catalogue and break `Stocktake::expected_count`.

#### `book_stocks` — quantity on hand at an exact location
| column | type |
|---|---|
| book_title_id | FK |
| shelf_section_id | FK |
| quantity | int, default 0 |
| reserved_quantity | int, default 0 |
| last_counted_at | timestamp null |

`unique(book_title_id, shelf_section_id)`. `available = quantity − reserved_quantity`.

**Reservation is what makes the workflow honest**: when an admin verifies a request,
the quantity is *reserved*, not deducted. Two centres can no longer be promised the
same 26 copies. Stock leaves `quantity` only at dispatch.

#### `stock_movements` — append-only ledger (the bin card)
| column | type | notes |
|---|---|---|
| book_title_id | FK | |
| shelf_section_id | FK null | null for pure paperwork corrections |
| type | string | `StockMovementType` enum |
| quantity | int | **always positive**; direction comes from `type` |
| balance_after | int | running balance *at that section*, for the bin card |
| unit_price / total_price | decimal null | mirrors the paper columns |
| reference_type / reference_id | morph null | PrintRun, BookDispatch, StockAudit… |
| reference_number | string null | CRV / invoice / waybill number |
| performed_by | FK users | |
| occurred_at | timestamp | may back-date a paper entry |
| description / remark | string null | the paper "Description" and "Remark" columns |

Never updated, never deleted. A mistake is corrected by a compensating movement. This
one table answers *"where did the 250 copies go?"* forever.

`StockMovementType`: `opening_balance`, `receipt`, `issue`, `return`, `transfer_in`,
`transfer_out`, `adjustment_increase`, `adjustment_decrease`, `damage`, `loss`,
`audit_surplus`, `audit_shortage`. Audit corrections are two cases rather than one so
the sign stays data, never a special case inside the ledger.

#### `print_runs` — stock in
`book_title_id, batch_number, quantity, unit_cost, total_cost, printer_name,
invoice_number, crv_number, printed_on, received_on, received_by, shelf_section_id,
notes`. Posting a print run writes a `receipt` movement and rolls `unit_cost` into the
title's weighted average.

#### `centers` — distribution centres
`name, code, city/region, coordinator_name, coordinator_phone, coordinator_user_id null,
student_count, campus_id null, is_active, notes`. Form A's header block.

#### `book_requests` — Form A
| column | notes |
|---|---|
| request_number | `BR-2026-0001`, generated |
| requester_id | FK users |
| destination_type | `center` \| `campus` (`RequestDestination` enum) |
| center_id / campus_id | one of the two |
| student_count | drives the availability sanity check |
| contact_name, contact_phone | coordinator on the paper form |
| status | `BookRequestStatus` enum |
| needed_by | date null |
| total_quantity, total_amount | denormalised, recomputed on item change |
| notes, rejection_reason | |
| verified_by/at, payment_verified_by/at, approved_by/at, dispatched_by/at, received_at | one pair per stage |

`book_request_items`: `book_request_id, book_title_id, quantity_requested,
quantity_approved, quantity_dispatched, unit_price, line_total, remark`.

`book_request_approvals`: `book_request_id, stage (BookRequestStage), actor_id,
decision (approved|rejected|returned), note, acted_at`. Append-only — the four signature
blocks on the paper form, with a name, a timestamp and an audit trail behind each.

#### `book_payments` — the money, explicitly as asked
`book_request_id, amount, method (BookPaymentMethod), bank_name, transaction_reference,
crv_number, receipt_number, paid_on, receipt_image_path, status (BookPaymentStatus),
recorded_by, verified_by, verified_at, rejection_reason, notes`.

Both references are captured because they serve different audits: the **bank transaction
reference** proves the money moved; the **manual CRV number** ties back to the paper
receipt book the finance office still keeps. The receipt image is stored on the private
disk and streamed through a controller — never a public URL.

#### `book_dispatches` / `book_dispatch_items` — Form B / Form C
`dispatch_number (BD-2026-0001), book_request_id, dispatched_by, dispatched_at,
received_by_name, received_by_phone, received_at, receipt_signature_path,
tracking_hash (QR on the waybill), status, notes`.
Items: `book_title_id, shelf_section_id, quantity, unit_price, line_total`.

Dispatch is where stock actually leaves: each item writes an `issue` movement, decrements
`book_stocks.quantity` and releases the matching reservation, inside one transaction.

#### `book_returns` / `book_return_items` — the right-hand half of Form B
`center_id/campus_id, book_dispatch_id null, returned_on, received_by, condition_note,
status`. Items carry `quantity_returned` and `quantity_damaged`, write `return`
movements and restore stock. **Outstanding = Σ dispatched − Σ returned** per centre per
title; that figure is the headline of the centre statement.

#### `stock_audits` / `stock_audit_lines` — counting & verification
`store_room_id, status (draft|in_progress|completed|approved|cancelled), started_by,
started_at, completed_at, approved_by, approved_at, notes`.
Lines: `shelf_section_id, book_title_id, system_quantity (frozen at start),
counted_quantity, variance (generated), counted_by, counted_at, note`.

Flow: start an audit for a store → the system snapshots expected quantities → the
counter walks the aisle **scanning each section's QR**, which opens the count sheet for
exactly that section → enters counts → completes → an approver reviews the variance
report and approves, which posts `audit_correction` movements for every non-zero
variance. Nothing silently changes stock; a human signs for every discrepancy.

---

## 3. The request workflow

```
        ┌──────────┐  submit   ┌───────────┐  verify   ┌──────────────────┐
        │  DRAFT   │──────────►│ SUBMITTED │──────────►│ AWAITING_PAYMENT │
        └──────────┘           └───────────┘  (stock   └──────────────────┘
                                     │        reserved)          │ finance verifies
                                     │ reject                    ▼
                                     ▼                  ┌──────────────────┐
                               ┌──────────┐             │ PAYMENT_VERIFIED │
                               │ REJECTED │◄────────────└──────────────────┘
                               └──────────┘   reject             │ admin final approval
                                                                 ▼
                                                          ┌────────────┐
                                                          │  APPROVED  │
                                                          └────────────┘
                                                                 │ store dispatches
                                            partial ┌────────────┴────────────┐
                                                    ▼                         ▼
                                       ┌──────────────────────┐        ┌────────────┐
                                       │ PARTIALLY_DISPATCHED │───────►│ DISPATCHED │
                                       └──────────────────────┘        └────────────┘
                                                                              │ receiver confirms
                                                                              ▼
                                                                        ┌──────────┐
                                                                        │ RECEIVED │
                                                                        └──────────┘
```

Cancellation is allowed from `draft`, `submitted`, `awaiting_payment` and
`payment_verified`; it releases reservations.

Each transition is **one method on one service** (`BookRequestWorkflow`) that:
1. asserts the current status allows it (`BookRequestStatus::can()`),
2. asserts the actor holds the stage permission,
3. mutates the request inside a DB transaction,
4. appends a `BookRequestApproval` row,
5. fires a domain event → notification to the next actor in the chain.

Because the guard lives in the enum and the service — not in the controller — the same
rules apply to the UI, the API and any future import. "Smooth, direct and predictable"
means there is exactly one path and the UI only ever shows the button whose transition
would succeed.

**Stage → permission → who actually does it:**

| Stage | Permission | Who |
|---|---|---|
| submit | `request_books` | Centre coordinator, campus rep |
| verify availability | `verify_book_request` | **Store Manager** — availability is checked by whoever can see the shelves |
| verify payment | `verify_book_payment` | Finance Manager |
| final approval | `approve_book_request` | Admin / holder of the approve grant |
| dispatch | `dispatch_books` | Store Manager |
| confirm receipt | `receive_books` | Requester |

### 3.2 Pay-later deferral

Finance may release the payment gate before the money is in. That is the one path
by which books leave the store unpaid, so it is deliberately the most constrained:

```
AWAITING_PAYMENT
   │
   ├── money verified ────────────────────────────► gate opens
   │
   └── Finance raises a deferral (REASON required)
            │  request_payment_bypass
            ▼
        PENDING ──► authoriser decides (JUSTIFICATION required)
            │           approve_payment_bypass — and never the same person
            ├── approved ──► gate opens, debt stays outstanding
            └── rejected ──► gate stays shut
```

Approving does **not** forgive the money. The amount remains on the request, the
deferral shows on the *Deferred payments* report, and it goes overdue once the
promised date passes — until somebody settles it. The approval trail records *why*
the gate opened: paid, or deferred under whose authority, naming the reference.

### 3.3 Notifications

Each hand-off notifies the holders of the permission that owns the **next** stage —
resolved from the permission itself, never from a role name or a configured list, so
granting somebody `verify_book_payment` starts their alerts and revoking it stops
them, with no second place to keep in step. The actor is never notified of their own
action. Database + mail, surfacing in the existing notification bell.

### 3.4 Stage timing and lag

Every step carries `acted_at` and a frozen `waited_seconds` — how long that stage sat
before somebody acted. Dwell time is therefore a plain average over one column rather
than a reconstruction across history. The pipeline board shows, per open request, the
stage, **who owes the next action by name**, how long it has waited, and total age;
the *Approval stage lag* report shows average and worst dwell time per layer per
person, which is the answer to "where is the lag".

`verify_book_request` and `verify_book_payment` are deliberately **different**
permissions from `approve_book_request`, so no single account can walk a request from
submission to dispatch. Segregation of duties is a hard requirement for anything that
touches money.

### 3.1 Availability check at verification

At verification the system computes, per line:

```
available = Σ book_stocks.quantity − Σ book_stocks.reserved_quantity   (across all sections)
```

and refuses to reserve more than is available. The verifier may reduce
`quantity_approved` below `quantity_requested` (partial approval) — the paper form
already does this informally. A sanity warning fires when
`quantity_requested > student_count` for a centre, because the request is per-student.

---

## 4. QR codes

One service, `App\Services\Bookstore\QrLabelService`, used by every QR in the module.

**Payload.** Every QR encodes an absolute URL:

```
https://sits.edu.et/bookstore/scan/{tracking_hash}
```

A URL, not a bare hash, so that *any* phone camera resolves it without our app. The
single `ScanController@resolve` endpoint looks the hash up across `book_titles`,
`store_rooms`, `shelves`, `shelf_sections` and `book_dispatches` and redirects to the
right screen — or, for a logged-in store keeper mid-audit, straight to the count sheet
for that section.

**Rendering.** Three endpoints per QR-able model:
- `GET …/{model}/qr` → PNG (`SimpleSoftwareIO\QrCode`, size 300, EC level `M`).
- `GET …/{model}/label` → an HTML label card: QR image with **the human name printed
  directly beneath it** (title + code, or the full `Store › Shelf › Section` path),
  sized for a 50×70 mm sticker, with `@media print` rules.
- `GET …/labels/print?ids[]=…` → a dompdf sheet of N labels, 3 across, for bulk
  printing after a print run or when a new shelf is racked.

Labels are generated **on demand, never stored** — the hash is immutable, so the image
is a pure function of the model, and there is no file to go stale. This mirrors the
existing `ShelfBoxController@qr`.

**What gets a QR** (all four asked for):
1. `BookTitle` — scan on the shelf to see the title, total on hand, and every location.
2. `ShelfSection` (and `Shelf`, `StoreRoom`) — scan to see contents, or to count.
3. `BookDispatch` — printed on the waybill; the receiving coordinator scans it to
   confirm receipt on their phone, which timestamps `received_at` and closes the loop
   without a phone call.

A print run does *not* get its own QR: its `batch_number` is unique and already printed
on the printer's delivery note, and the copies themselves are labelled with the title QR.

---

## 5. Low stock, alerts, reporting

**Low stock.** `BookTitle::reorder_level`. A title is *low* when
`total_on_hand ≤ reorder_level` and *out* when `total_on_hand = 0`. Surfaced in three
places: a red band on the bookstore dashboard, a filterable "Low stock" report, and a
scheduled `bookstore:check-stock` command (daily) that notifies every user holding
`manage_book_stock` about titles that crossed the threshold **since the last run** —
threshold-crossing, not "still low", so the alert does not become noise.

**Reports** (all exportable to CSV/XLSX via the existing `maatwebsite/excel`, and to PDF
via dompdf):
1. **Stock on hand** — by title, program, language, study mode, store, shelf, section;
   with valuation at `unit_cost` and at `unit_price`.
2. **Bin card** — per title per section, the paper-identical movement ledger.
3. **Movement summary** — received / issued / returned / adjusted for a date range.
4. **Distribution by centre** — issued, returned, outstanding, value outstanding.
   This is the report the seminary currently cannot produce at all.
5. **Outstanding returns** — Form B's unreturned column, aged.
6. **Request pipeline** — how many requests sit at each stage, and for how long
   (stage ageing exposes the bottleneck).
7. **Payments** — collected vs expected, by method, with CRV traceability.
8. **Audit variance** — every count, every discrepancy, who signed.
9. **Reprint forecast** — consumption rate per title × weeks of cover remaining.
10. **Approval stage lag** — average and worst dwell time per layer per person.
11. **Deferred payments** — every pay-later release, its reason, its authorisation
    and whether the promise has come due.

---

## 6. Implementation phases

Each phase is independently shippable and testable.

| Phase | Deliverable | Files |
|---|---|---|
| **0** | Enums + permissions + migrations | `app/Enums/*`, `database/migrations/*`, `BookstorePermissionsSeeder` |
| **1** | Location tree + QR (store rooms, shelves, sections, labels, scan resolver) | `StoreRoomController`, `ShelfController`, `ShelfSectionController`, `ScanController`, `QrLabelService` |
| **2** | Catalogue: book titles, study modes, print runs, opening balances | `BookTitleController`, `StudyModeController`, `PrintRunController`, `StockLedger` |
| **3** | Stock engine: `BookStock`, `StockMovement`, transfers, adjustments, bin card | `StockLedger`, `StockTransferController`, `StockAdjustmentController` |
| **4** | Request workflow + approvals + notifications | `BookRequestController`, `BookRequestWorkflow`, `BookRequestApprovalController` |
| **5** | Payments (CRV, bank ref, receipt image, verification) | `BookPaymentController` |
| **6** | Dispatch + waybill print + receipt confirmation | `BookDispatchController` |
| **7** | Returns & centre reconciliation | `BookReturnController` |
| **8** | Stock audits (QR-driven counting, variance approval) | `StockAuditController` |
| **9** | Dashboard, reports, exports, low-stock command | `DashboardController`, `ReportController`, `CheckBookStock` |

### 6.1 Testing

Pest feature tests, `RefreshDatabase`, one file per phase under
`tests/Feature/Bookstore/`. The non-negotiable cases:

- ledger balance is monotonic and `balance_after` matches a recomputation from zero;
- a request cannot skip a stage (every illegal transition throws);
- the same user cannot both verify and approve;
- reserving twice cannot exceed available stock (concurrency: `lockForUpdate`);
- dispatch decrements exactly once even if the endpoint is double-submitted
  (idempotency on `dispatch_number`);
- an audit posts corrections only on approval, and only for non-zero variance;
- QR hash resolves to the right model type and 404s on an unknown hash.

### 6.2 Deployment note

`public/build` is committed (cPanel has no Node). Every phase that touches
`resources/js` must ship `npm run build` output in the same commit — see
`docs/deploy-to-cpanel.md`.

---

## 7. Design decisions worth stating

1. **Quantity, not copies.** Modelling 250 printed sociology books as 250 `BookCopy`
   rows would be technically possible and operationally absurd. Quantity + ledger is
   the correct model for consumables, and it is what the pink bin card already does.
2. **Append-only ledger.** `book_stocks.quantity` is a cache; `stock_movements` is the
   truth. Any balance can be recomputed from the ledger, which is what makes an audit
   defensible.
3. **Reserve at verification, deduct at dispatch.** The gap between "approved" and
   "in the truck" is days; without reservation the availability figure lies for that
   whole window.
4. **Separate location tree from the ILS.** Same reason a warehouse is not a reading
   room.
5. **Denormalised course code/name on `book_titles`.** Courses get renamed; a book
   printed in 2024 must still show the code it was printed under.
6. **Segregation of duties enforced by distinct permissions**, not by convention.
7. **QR payload is a URL.** Bare hashes require our scanner app; URLs work with the
   camera every coordinator already has.
