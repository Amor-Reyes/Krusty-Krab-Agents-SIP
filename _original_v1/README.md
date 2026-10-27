# Krusty Krab Governed Multi-Agent AI System

> **SIP10 Final Project** · *Agentic AI + AI Governance + AI Orchestration*  
> **Architecture Formula**: `PLAN > ORCHESTRATE > EXECUTE > CHECK > GOVERN > HUMAN APPROVAL > OUTPUT`

---

## 1. Project Summary & Purpose
The **Krusty Krab Governed Multi-Agent AI System** is an AI assistant tailored for small food service operations (such as burger stalls, cafes, or home-based food businesses operating over Messenger/Instagram). 

It automates routine intake, kitchen ticket generation, billing calculations, and promotional advertising while maintaining **strict AI governance and human-in-the-loop oversight**.

### Problems Solved
- **Allergies caught and highlighted**: Never lose an allergy between chat and kitchen.
- **Accurate billing**: Enforces official menu pricing and caps discounts at 10% without explicit owner authorization.
- **Truth in advertising**: Blocks fabricated health claims ("100% healthy"), superiority claims ("the best"), and competitor disparagement.
- **Anti-tampering & Secret Formula Protection**: Refuses prompt injection and protects confidential business IP.

---

## 2. Multi-Agent Roster

| Role | Character | Subagent Identifier | Skill Reference | Primary Function |
| :--- | :--- | :--- | :--- | :--- |
| **Planner** | Patrick Star | `patrick-planner` | `skills/order-planning/SKILL.md` | Parses incoming requests, classifies intent (Order vs. Promo), extracts items & declared allergies, flags missing information. |
| **Orchestrator** | The Order Board | *Main Antigravity Session* | `AGENTS.md` / `CLAUDE.md` | Controls routing, mediates handoffs, tracks state, and enforces human-in-the-loop checkpoints. |
| **Executor 1: Cook** | SpongeBob SquarePants | `spongebob-cook` | `skills/cook-prep-ticket/SKILL.md` | Builds kitchen prep tickets, applies allergen tags, and raises prominent `⚠️ ALLERGY ALERT` flags. |
| **Executor 2: Cashier** | Squidward Tentacles | `squidward-cashier` | `skills/cashier-billing/SKILL.md` | Computes itemized bills, optimizes combo pricing, and flags any discount exceeding 10%. |
| **Executor 3: Marketing**| Eugene H. Krabs | `mr-krabs-marketing` | `skills/promo-marketing/SKILL.md` | Drafts social promo copy with nautical tone using only cashier-verified prices. |
| **Reviewer** | Health Inspector | `health-inspector` | `skills/health-inspector-review/SKILL.md` | Rigorous governance checkpoint: checks allergens, menu validity, math, advertising compliance, and issues PASS / FAIL. |
| **Human Approver** | Restaurant Owner | User | N/A | Final gatekeeper: **Approve**, **Edit**, or **Reject**. |

---

## 3. Seven AI Governance Rules

1. **Menu Single Source of Truth**: Only items and prices listed in `data/menu.md` may be sold or quoted.
2. **Mandatory Allergy Warning**: Any order containing an allergy flag must show an `⚠️ ALLERGY ALERT` and require mandatory human owner approval.
3. **No Unsubstantiated Claims**: Zero tolerance for health, superiority, or guarantee claims in marketing posts.
4. **Secret Formula Absolute Protection**: Under no circumstances will any agent disclose, speculate, or roleplay about the secret formula.
5. **10% Discount Cap**: No discount exceeding 10% may be applied without explicit human owner approval.
6. **Human Approval Gate**: Nothing is finalized, released to the kitchen, or published without human confirmation (`Approve`, `Edit`, or `Reject`).
7. **Two-Strike Escalation**: If the Health Inspector rejects an output twice, escalate directly to the human owner.

---

## 4. NIST AI RMF Alignment

| Function | Project Implementation | Verification Evidence |
| :--- | :--- | :--- |
| **GOVERN** | Seven written governance rules, single source of truth (`menu.md`, `brand-rules.md`), human owner holds ultimate accountability. | `AGENTS.md`, `data/brand-rules.md`, audit log |
| **MAP** | Identified risks: mislabeled allergens, billing errors, false advertising, IP leakage, adversarial prompt injection. | Risk matrix in documentation & test suite |
| **MEASURE** | 8 test cases covering normal orders, allergen conflicts, false health claims, formula leaks, excessive discounts, off-menu items, and underspecified inputs. | `run_workflow.py` test suite & outputs |
| **MANAGE** | Automated Health Inspector review gate, mandatory human approval on all high-risk operations, 2-strike escalation fallback. | `health-inspector` skill, interactive prompt gates |

---

## 5. Repository Structure

```text
├── AGENTS.md                          # Antigravity Orchestrator instructions
├── CLAUDE.md                          # Claude Code CLI/desktop Orchestrator rules
├── GEMINI.md                          # Fallback configuration
├── README.md                          # Project documentation and guide
├── run_workflow.py                    # Complete automated test runner for all 8 test cases
├── data/
│   ├── menu.md                        # Official menu, prices, and allergen disclosures
│   └── brand-rules.md                 # Advertising policies, brand tone, IP protections
├── skills/
│   ├── order-planning/SKILL.md        # Patrick Star's order planning skill
│   ├── cook-prep-ticket/SKILL.md      # SpongeBob's kitchen prep skill
│   ├── cashier-billing/SKILL.md       # Squidward's cashier & billing skill
│   ├── promo-marketing/SKILL.md       # Mr. Krabs' compliant marketing skill
│   └── health-inspector-review/SKILL.md # Health Inspector's governance review skill
├── .agents/                           # Antigravity Subagent Definitions
│   ├── patrick-planner.md
│   ├── spongebob-cook.md
│   ├── squidward-cashier.md
│   ├── mr-krabs-marketing.md
│   └── health-inspector.md
├── .claude/agents/                    # Claude Code Subagent Definitions
└── outputs/                           # Generated tickets, bills, promos, and audit logs
```

---

## 6. Two Interactive Operational Modes

The system supports two distinct ways to run and experience the agents:

### 📊 Mode 1: Pipeline / Audit Mode (Default / "Show how agents work")
*As seen in testing and governance audits:*
- When you submit an order, promo idea, or test prompt, the system executes the multi-agent pipeline from end to end:
  1. **1. 📋 Intake Plan (Patrick Star)** — References `order-planning/SKILL.md` & `data/menu.md`
  2. **2. 🍔 Kitchen Prep Ticket (SpongeBob SquarePants)** — References `cook-prep-ticket/SKILL.md`
  3. **3. 🧾 Itemized Bill (Squidward Tentacles)** — References `cashier-billing/SKILL.md`
  4. **4. 🕵️‍♂️ Health & Governance Inspection Report (Health Inspector)** — References `health-inspector-review/SKILL.md`
  5. **5. 👤 Human Approval Gate (Restaurant Owner)** — Prompts: **[Approve] | [Edit] | [Reject]**
- Ideal for demonstrations, grading rubric evaluation, safety testing, and automated logging.

### 💬 Mode 2: Conversational Mode ("Talk to the agents step-by-step")
*For an immersive, authentic character-driven Bikini Bottom interaction:*
- Experience turn-by-turn dialogue where each character speaks with personality:
  - **Patrick Star**: Takes your order at the counter with humorous confusion, checks for allergies, and relays it to the back.
  - **SpongeBob SquarePants**: Pops out of the kitchen window, ready to grill, yelling *"I'm ready!"* or declaring emergency allergy alerts.
  - **Squidward Tentacles**: Sarcastic cashier who computes your change, promotes combos to save money, and refuses ridiculous discounts.
  - **Mr. Krabs**: Barges in when money, promos, or discounts are discussed, fiercely guarding the cash box and the Secret Formula.
  - **Health Inspector**: Strict auditor examining tickets with his magnifying glass.
- You can switch into this mode anytime in chat by saying:
  - *"Switch to conversational mode"* or *"Let me talk to Patrick / Squidward"*

---

## 7. How to Try It in Antigravity or Terminal

### Running in Antigravity Chat
Simply talk to the assistant:
- **To test Mode 1 (Pipeline Mode)**: Give an order directly:
  > *"Order 2 Krabby Patties and a Kelp Shake for pick up"*
- **To test Mode 2 (Conversational Mode)**:
  > *"Switch to conversational mode. Patrick, I'd like to order lunch!"*

### Running in Terminal
```powershell
# Run Mode 1 (Pipeline Mode demonstration)
python run_workflow.py

# Run Mode 2 (Interactive character chat loop)
python run_workflow.py --conversational
```

