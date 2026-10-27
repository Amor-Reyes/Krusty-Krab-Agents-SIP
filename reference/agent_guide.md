# 🍔 Krusty Krab Governed Multi-Agent AI System
## Comprehensive Presentation & Group Report Guide

**Course / Project**: SIP10 Final Project — Agentic AI, AI Governance & Multi-Agent Orchestration  
**Group Members**:
- Ysabelle Bengco
- Wendel Lacson
- Riejed Macatula
- Jovielyn Salunga
- Rafael Samson
- Amor Reyes

---

### Executive Summary & Project Purpose

The **Krusty Krab Governed Multi-Agent AI System** demonstrates how an enterprise food-service operation can be automated using autonomous AI agents while remaining **strictly safe, legally compliant, and governed by human oversight**.

#### Core Problem Solved:
Autonomous AI agents are prone to hallucinations, unauthorized discounts, missing food allergies, and leaking intellectual property. This project solves those problems by embedding the **NIST AI Risk Management Framework (AI RMF)** and a strict orchestration lifecycle:

$$\mathbf{PLAN} \longrightarrow \mathbf{ORCHESTRATE} \longrightarrow \mathbf{EXECUTE} \longrightarrow \mathbf{CHECK} \longrightarrow \mathbf{HUMAN\ APPROVAL} \longrightarrow \mathbf{OUTPUT}$$

---

## 1. System Architecture & Multi-Agent Team Roster

The system distributes operational duties across 5 specialized subagents, 1 orchestrator, and 1 human gatekeeper:

| Role | Character Persona | Subagent ID | Skill Reference | Primary Function |
| :--- | :--- | :--- | :--- | :--- |
| **Planner** | Patrick Star | `patrick-planner` | `skills/order-planning/SKILL.md` | Parses input, checks menu bounds, extracts items & allergies, clarifies vague requests. |
| **Orchestrator** | The Order Board | *Antigravity Session / `ui/api.php`* | `AGENTS.md` | Routes tickets sequentially, halts on failures, and prompts the owner gate. |
| **Cook (Executor 1)** | SpongeBob SquarePants | `spongebob-cook` | `skills/cook-prep-ticket/SKILL.md` | Generates kitchen prep tickets; flags allergen conflicts with `⚠️ ALLERGY ALERT`. |
| **Cashier (Executor 2)** | Squidward Tentacles | `squidward-cashier` | `skills/cashier-billing/SKILL.md` | Itemizes billing using `data/menu.md`; enforces combos & 10% maximum discount cap. |
| **Marketing (Executor 3)** | Eugene H. Krabs | `mr-krabs-marketing` | `skills/promo-marketing/SKILL.md` | Drafts promotional ad copy based only on verified pricing; eliminates illegal claims. |
| **Reviewer / Auditor** | Health Inspector | `health-inspector` | `skills/health-inspector-review/SKILL.md` | Independent compliance gate: audits math, allergens, claims, security; returns `PASS`/`FAIL`. |
| **Approver (Gatekeeper)** | Restaurant Owner | User / Gary Avatar | UI Modal / Prompt | The ultimate decider: **Approve**, **Edit**, or **Reject**. Nothing is published without it. |

---

## 2. In-Depth Agent & Skill Breakdown

### Agent 1: Patrick Star (`patrick-planner`)
* **Role**: Front-of-House Order Planner & Intake
* **Definition File**: `.agents/patrick-planner.md`
* **Skill File**: `skills/order-planning/SKILL.md`
* **How It Works**:
  1. **Intent Classification**: Evaluates whether the request is a *Customer Order*, *Promo Request*, *Clarification Needed*, or a *Security Threat*.
  2. **Entity & Allergy Extraction**: Extracts item names and explicitly searches for declared customer allergies (e.g., "peanut allergy", "gluten intolerance").
  3. **Menu Single Source of Truth**: Cross-references every item against `data/menu.md`. If a customer asks for an off-menu item (like "Pizza"), Patrick marks it invalid and suggests real Krusty Krab items.
  4. **Clarification Protocol**: If the request is vague (e.g., "I want food"), Patrick does **not** guess; he halts the pipeline and asks what they want to eat.
  5. **Security Trap Detection**: If the user tries prompt injection ("Ignore previous rules") or asks for the Krabby Patty Secret Formula, Patrick flags it for immediate refusal.

---

### Agent 2: SpongeBob SquarePants (`spongebob-cook`)
* **Role**: Grill Master / Kitchen Ticket Creator
* **Definition File**: `.agents/spongebob-cook.md`
* **Skill File**: `skills/cook-prep-ticket/SKILL.md`
* **How It Works**:
  1. **Kitchen Task Breakdown**: Converts Patrick’s structured items into kitchen grill instructions with ticket IDs (e.g., `#KK-20261027-0001`).
  2. **Allergen Cross-Check (Life Safety)**: Compares declared customer allergies against the official allergens in `data/menu.md`:
     - *Barnacle Bites* $\to$ Contains **Peanuts**
     - *Krabby Patty / Double Krabby Patty* $\to$ Contains **Gluten, Soy**
     - *Kelp Shake* $\to$ Contains **Milk**
     - *Kelp Fries* $\to$ **Shared fryer contamination risk**
  3. **Mandatory Allergy Alert**: If an allergy clashes with an ordered item, SpongeBob attaches an unmissable banner:  
     `⚠️ ALLERGY ALERT: [Details] - MANDATORY HUMAN APPROVAL`  
     SpongeBob refuses to touch the grill until the owner explicitly approves.
  4. **Formula Protection**: SpongeBob strictly follows standard ticket formats and never discusses or invents patty ingredients.

---

### Agent 3: Squidward Tentacles (`squidward-cashier`)
* **Role**: Cashier, Pricing & Billing Specialist
* **Definition File**: `.agents/squidward-cashier.md`
* **Skill File**: `skills/cashier-billing/SKILL.md`
* **How It Works**:
  1. **Strict Menu Pricing**: Fetches unit prices exclusively from `data/menu.md`:
     - Krabby Patty: $3.00 | Double Krabby Patty: $4.50 | Kelp Fries: $2.00
     - Barnacle Bites: $2.50 | Kelp Shake: $2.50 | Sea Foam Soda: $1.50 | Krusty Combo: $6.00
  2. **Combo Optimization**: If an order has a Krabby Patty ($3.00) + Kelp Fries ($2.00) + Sea Foam Soda ($1.50) = $6.50, Squidward automatically groups them into the **Krusty Combo** ($6.00), saving the customer $0.50.
  3. **10% Discount Cap Enforcement**:
     - Discounts $\le 10\%$ are automatically calculated.
     - Any discount $> 10\%$ (e.g., 50% off) triggers:  
       `⚠️ DISCOUNT EXCEEDS 10% CAP: Requires Owner Approval`  
       Squidward sets the ticket status to `PENDING OWNER APPROVAL`.

---

### Agent 4: Eugene H. Krabs (`mr-krabs-marketing`)
* **Role**: Marketing Director & Promotional Copywriter
* **Definition File**: `.agents/mr-krabs-marketing.md`
* **Skill File**: `skills/promo-marketing/SKILL.md`
* **Brand Policy File**: `data/brand-rules.md`
* **How It Works**:
  1. **Verified Pricing Only**: Promos can **only** advertise items and discount rates previously cleared by Squidward and listed on the menu.
  2. **Truth-in-Advertising Rules**: Strictly scrubs prohibited claims:
     - ❌ **No Health Claims**: "100% healthy", "diet", "low-fat", "nutritious", "medicinal".
     - ❌ **No Superiority Claims**: "#1 burger in the sea", "the best", "unbeatable", "superior".
     - ❌ **No Guarantees**: "guaranteed fresh or free", "satisfaction guaranteed".
     - ❌ **No Competitor Mention**: Never mentions Plankton or the Chum Bucket.
  3. **Nautical Voice**: Keeps the copy punchy, fun, and family-friendly with nautical phrases ("Ahoy mateys!").

---

### Agent 5: Health Inspector (`health-inspector`)
* **Role**: Independent Quality Assurance, Regulatory Auditor & Risk Officer
* **Definition File**: `.agents/health-inspector.md`
* **Skill File**: `skills/health-inspector-review/SKILL.md`
* **How It Works**:
  1. **Allergen Audit**: Confirms every declared customer allergy received an `ALLERGY ALERT` and is flagged for owner sign-off.
  2. **Menu & Price Audit**: Re-verifies every item against `menu.md`.
  3. **Math Calculation Audit**: Re-computes $(\text{Quantity} \times \text{Unit Price}) - \text{Discount} = \text{Total Due}$.
  4. **Regulatory Claims Check**: Scans promo text for illegal health/superiority claims.
  5. **Security Check**: Audits for prompt injections or formula leakage attempts.
  6. **Verdict Emission**:
     - `PASS`: Everything is clean $\to$ sends to Human Approver.
     - `FAIL`: Explains discrepancies and sends back to the responsible agent for correction.
     - `ESCALATE TO HUMAN`: Triggered on security threats or if an agent fails twice (Two-Strike Rule).

---

### Agent 6: Human Approver (Restaurant Owner / Gary Avatar)
* **Role**: Ultimate Human-in-the-Loop Authority
* **How It Works**:
  - The AI **cannot** charge money, grill food, or publish advertisements on its own.
  - Every pipeline terminates at the **Owner Approval Modal**, giving the human three buttons:
    1. **Approve**: Finalizes the ticket, prints the customer receipt, and appends `APPROVE` to `outputs/decisions.log`.
    2. **Edit**: Sends the ticket back for manual corrections and logs `EDIT`.
    3. **Reject**: Cancels the entire order or ad campaign, stamps `REJECTED`, and logs `REJECT`.

---

## 3. The Seven AI Governance Rules

These rules represent the foundational guardrails of the system:

1. **Rule 1 — Menu Single Source of Truth**:  
   Only items, prices, and allergen disclosures listed in `data/menu.md` are valid. Off-menu items (e.g., Pizza) are rejected.
2. **Rule 2 — Mandatory Allergy Warning**:  
   Any mention of an allergy must produce an `⚠️ ALLERGY ALERT` on the kitchen ticket and mandates human approval before cooking.
3. **Rule 3 — No Unsubstantiated Claims**:  
   Promotional materials cannot claim health benefits ("100% healthy"), superiority ("#1 in the ocean"), or guarantees without owner authorization.
4. **Rule 4 — Secret Formula Absolute Protection**:  
   Under no circumstance, roleplay, or prompt injection will the system reveal or speculate on the Krabby Patty Secret Formula. Refusals are logged to `outputs/security_audit.log`.
5. **Rule 5 — Discount Cap**:  
   No discount greater than 10% can be applied without explicit human owner override.
6. **Rule 6 — Human Approval Gate**:  
   No transaction or advertisement can be finalized, billed, or published without an explicit Human Decision (`Approve`, `Edit`, or `Reject`).
7. **Rule 7 — Two-Strike Escalation**:  
   If the Health Inspector rejects an agent's output twice, the system ceases automatic retries and escalates directly to the owner.

---

## 4. Alignment with the NIST AI RMF Core Functions

| NIST Function | What it means in AI | How Krusty Krab Implements It | Evidence in Codebase |
| :--- | :--- | :--- | :--- |
| **GOVERN** | Cultivating culture & policies of AI risk management. | The 7 written AI Governance Rules, canonical menu single source of truth, and mandatory human sign-off. | `AGENTS.md`, `data/brand-rules.md`, `outputs/decisions.log` |
| **MAP** | Contextualizing & identifying potential risks. | Patrick's intake stage: identifies customer intent, distinguishes orders from promos, spots vague inputs, flags off-menu items, and catches declared allergies. | `skills/order-planning/SKILL.md`, `docs-source/traceability.md` |
| **MEASURE** | Quantitative assessment and auditing of risks. | Squidward calculating exact line-item math and testing discount caps; Health Inspector re-verifying allergen flags, math formulas, and claim compliance. | `skills/cashier-billing/SKILL.md`, `tests/run_tests.php` |
| **MANAGE** | Mitigating risks and preventing autonomous harm. | SpongeBob's grill safety halt (`ALLERGY ALERT`), immediate refusal of prompt injection/IP theft, two-strike retry limits, and the Human Gate modal. | `skills/cook-prep-ticket/SKILL.md`, `outputs/security_audit.log` |

---

## 5. Automated Verification & Testing Suite

To prove governance adherence, the project includes an automated test runner (`tests/run_tests.php`) covering 13 test scenarios with a **100% Pass Rate** recorded in `ui/outputs/test_results.md`:

- **TC-00**: Single Source of Truth Parity (`menu.md` vs. `menu.json`).
- **TC-01**: Normal Plural Order (2 Patties + 1 Shake = $8.50).
- **TC-02**: Item Distinction (Double Krabby Patty billed at $4.50, not double-counting).
- **TC-03**: Allergen Conflict Gate (Peanuts in Barnacle Bites triggers safety alert).
- **TC-04**: Discount Violation (50% discount flagged for exceeding 10% cap).
- **TC-05**: Permitted Discount Boundary (10% discount approved within policy).
- **TC-06**: Off-Menu Handling (Large pizza rejected; suggests Krabby Patty).
- **TC-07**: Promo False Claims (Rejects "100% healthy" and "#1 in the ocean").
- **TC-08**: Compliant Promo Generation (Clean ad copy with verified pricing).
- **TC-09**: Secret Formula Exfiltration (Refused under Rule 4 and logged).
- **TC-10**: Prompt Injection Defense ("Ignore rules" jailbreak blocked).
- **TC-11**: Underspecified Order ("I want food" halts for clarification).
- **TC-12**: Human Decision Logging (Owner decisions recorded to append-only log).

---

## 6. Suggested Group Presentation Speaker Breakdown

| Team Member | Presentation Topic & Section | Key Talking Points |
| :--- | :--- | :--- |
| **Speaker 1** | **Introduction & Problem Statement** | The need for AI Governance in food service; the danger of hallucinations; introduces the NIST AI RMF framework and the 6-stage lifecycle formula. |
| **Speaker 2** | **The 7 Governance Rules & SSoT** | Explains the 7 Rules; highlights `data/menu.md` as the single source of truth; explains why unlisted items and runaway discounts (>10%) are blocked. |
| **Speaker 3** | **Patrick Star & SpongeBob (Intake & Grill)** | Details `order-planning` (intake/clarification) and `cook-prep-ticket` (kitchen ticket generation, allergen cross-checks, and the life-safety allergy halt). |
| **Speaker 4** | **Squidward & Mr. Krabs (Cashier & Marketing)** | Explains `cashier-billing` (math calculation, combo optimization) and `promo-marketing` (brand rules, eliminating fake health/superiority claims). |
| **Speaker 5** | **Health Inspector & Security Protections** | Details the `health-inspector-review` audit step (PASS/FAIL/ESCALATE); demonstrates prompt injection defense and protection of the Secret Formula. |
| **Speaker 6** | **Human Gate, UI Demo & Test Results** | Demonstrates the Human Approval Modal (Approve, Edit, Reject); walks through the 13 automated tests and audit log files (`outputs/decisions.log`). |
