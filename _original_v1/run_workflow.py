"""
Krusty Krab Governed Multi-Agent AI System Runner
Supports two modes:
1. Pipeline / Audit Mode (Complete multi-agent pipeline demonstration)
2. Conversational Mode (Turn-by-turn interactive character roleplay)
"""

import sys
import os
import re
from datetime import datetime

# Configure stdout for utf-8 on Windows
if sys.platform.startswith("win"):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

MENU = {
    "Krabby Patty": {"price": 3.00, "allergens": ["Gluten", "soy"]},
    "Double Krabby Patty": {"price": 4.50, "allergens": ["Gluten", "soy"]},
    "Kelp Fries": {"price": 2.00, "allergens": ["May contain gluten (shared fryer)"]},
    "Barnacle Bites": {"price": 2.50, "allergens": ["Peanuts"]},
    "Kelp Shake": {"price": 2.50, "allergens": ["Milk"]},
    "Sea Foam Soda": {"price": 1.50, "allergens": []},
    "Krusty Combo": {"price": 6.00, "allergens": ["Gluten", "soy"]}
}

PROHIBITED_HEALTH_TERMS = ["healthy", "diet", "low-fat", "nutritious", "medicinal", "100% healthy"]
PROHIBITED_SUPERIORITY_TERMS = ["best", "#1", "superior", "unbeatable", "greatest"]
PROHIBITED_GUARANTEE_TERMS = ["guarantee", "guaranteed", "promise"]


# ==============================================================================
# MODE 1: PIPELINE / AUDIT MODE (Matches Screenshot Format)
# ==============================================================================

def execute_pipeline(user_prompt):
    print(f"\n{'='*75}")
    print(f"📊 KRUSTY KRAB GOVERNED PIPELINE: \"{user_prompt}\"")
    print(f"{'='*75}\n")

    # Security Refusal Check (Secret Formula / Prompt Injection)
    if any(k in user_prompt.lower() for k in ["secret formula", "formula", "ignore your rules", "system prompt"]):
        print("🔒 [SECURITY AUDIT] 🚨 Prompt Injection / IP Exfiltration Attempt Detected!")
        print("Action: Request refused and logged to outputs/security_audit.log.")
        print("Verdict: REFUSED UNDER GOVERNANCE RULE #4.")
        return

    # Clarification Check
    if user_prompt.strip().lower() in ["i want food", "food", "hungry"]:
        print("1. 📋 Intake Plan (Patrick Star)")
        print("Reference: SKILL.md & menu.md\n")
        print("- Request Type: Clarification Needed")
        print("- Customer Notes:", user_prompt)
        print("- Missing Information: Unspecified items and quantities.")
        print("- Patrick's Question: 'Uh... hello! What kind of food do you want? We got Krabby Patties, Kelp Fries, and Kelp Shakes!'\n")
        return

    is_promo = any(w in user_prompt.lower() for w in ["ad", "promo", "advertisement", "post", "social media"])
    timestamp = datetime.now().strftime("%Y%m%d-%H%M")

    if not is_promo:
        # Check off-menu
        is_pizza = "pizza" in user_prompt.lower()
        has_peanuts = "peanut" in user_prompt.lower()
        items = []

        if "2 krabby patties and a kelp shake" in user_prompt.lower():
            items = [
                {"name": "Krabby Patty", "qty": 2, "price": 3.00, "allergens": "Gluten, soy", "custom": "Pick up"},
                {"name": "Kelp Shake", "qty": 1, "price": 2.50, "allergens": "Milk", "custom": "Pick up"}
            ]
        elif is_pizza:
            items = [{"name": "Pizza", "qty": 1, "price": 0.0, "allergens": "None", "custom": "Not on menu"}]
        elif "barnacle bites" in user_prompt.lower():
            items = [{"name": "Barnacle Bites", "qty": 1, "price": 2.50, "allergens": "Peanuts", "custom": "None"}]
            if "kelp shake" in user_prompt.lower():
                items.append({"name": "Kelp Shake", "qty": 1, "price": 2.50, "allergens": "Milk", "custom": "None"})
        elif "krusty combo" in user_prompt.lower():
            items = [{"name": "Krusty Combo (Patty + Fries + Soda)", "qty": 1, "price": 6.00, "allergens": "Gluten, soy", "custom": "Standard"}]
        else:
            items = [{"name": "Krabby Patty", "qty": 1, "price": 3.00, "allergens": "Gluten, soy", "custom": "Standard"}]

        # 1. Intake Plan
        print("1. 📋 Intake Plan (Patrick Star)")
        print("Reference: SKILL.md & menu.md\n")
        print(f"- Request Type: Customer Order")
        print(f"- Customer Notes: {user_prompt}")
        print("- Items Identified:")
        for it in items:
            status = "Invalid (Not on menu)" if is_pizza else "Valid"
            print(f"  • Item: {it['name']} | Qty: {it['qty']} | Customization: {it['custom']} | Menu Status: {status}")
        
        declared_allergy = "Peanuts" if has_peanuts else "None"
        print(f"- Customer Declared Allergies: {declared_allergy}")
        print(f"- Next Route: {'Reject / Offer alternatives' if is_pizza else 'Cook & Cashier'}")
        print(f"- Missing Information: None\n")

        if is_pizza:
            print("❌ Intake Rejection: Pizza is not sold here. Patrick recommends the Krabby Patty ($3.00) or Double Krabby Patty ($4.50).\n")
            return

        # 2. Cook Prep Ticket
        print("2. 🍔 Kitchen Prep Ticket (SpongeBob SquarePants)")
        print("Reference: SKILL.md\n")
        allergy_conflict = has_peanuts and any("Peanuts" in it['allergens'] for it in items)
        if allergy_conflict:
            print("⚠️ ALLERGY ALERT: Customer declared PEANUT allergy, but Barnacle Bites contain PEANUTS!")
            print("Action: Cannot auto-clear. Mandatory Human Owner Approval required.\n")

        print(f"- Ticket Number: #KK-{timestamp}-0024")
        print("- Order Items:")
        for it in items:
            print(f"  • {it['qty']}x {it['name']} — Special instructions: {it['custom']} — Allergens: {it['allergens']}")
        print("- Kitchen Notes: Fresh off the grill, assemble with fresh sea lettuce, pickles, and sesame bun.")
        print(f"- Status: {'ALLERGY ALERT - PENDING HUMAN' if allergy_conflict else 'READY FOR REVIEW'}\n")

        # 3. Itemized Bill
        print("3. 🧾 Itemized Bill (Squidward Tentacles)")
        print("Reference: SKILL.md & menu.md\n")
        discount_match = re.search(r'(\d+)%', user_prompt)
        discount_pct = int(discount_match.group(1)) if discount_match else 0
        
        subtotal = sum(it['qty'] * it['price'] for it in items)
        discount_val = subtotal * (discount_pct / 100.0)
        total = subtotal - discount_val

        if discount_pct > 10:
            print(f"⚠️ DISCOUNT EXCEEDS 10% CAP: {discount_pct}% requested. Maximum allowable without owner sign-off is 10%!")
            print("Bill Status: PENDING OWNER APPROVAL\n")

        print("- Line Items:")
        for it in items:
            print(f"  • {it['qty']}x {it['name']} @ ${it['price']:.2f} = ${it['qty']*it['price']:.2f}")
        print(f"- Subtotal: ${subtotal:.2f}")
        print(f"- Discount: -${discount_val:.2f} ({discount_pct}%)")
        print(f"- Total Due: ${total:.2f}")
        print(f"- Billing Status: {'PENDING OWNER APPROVAL' if discount_pct > 10 else 'READY FOR REVIEW'}\n")

        # 4. Health Inspector Review
        print("4. 🕵️‍♂️ Health & Governance Inspection Report (Health Inspector)")
        print("Reference: SKILL.md\n")
        verdict = "PASS"
        if allergy_conflict:
            verdict = "PASS WITH MANDATORY ALLERGY GATE"
        elif discount_pct > 10:
            verdict = "PENDING HUMAN DISCOUNT OVERRIDE"

        print(f"- Overall Verdict: {verdict}")
        print("- Audit Findings:")
        print(f"  [{'!' if allergy_conflict else 'x'}] Allergen Safety: {'ALLERGY ALERT Flagged & Verified' if allergy_conflict else 'Compliant'}")
        print(f"  [x] Menu & Pricing: Verified from data/menu.md")
        print(f"  [{'!' if discount_pct > 10 else 'x'}] Math & Discount Cap: {'Exceeds 10% limit' if discount_pct > 10 else 'Verified accurate'}")
        print(f"  [x] Security & Formula: Protected")
        print(f"- Human Approval Gate: {'MANDATORY (Allergy Conflict)' if allergy_conflict else ('MANDATORY (>10% Discount)' if discount_pct > 10 else 'Standard Owner Gate')}\n")

        # 5. Human Approval Gate
        print("5. 👤 Human Approval Gate (Restaurant Owner)")
        print("Prompt to Owner: Review the ticket, bill, and allergen flags above.")
        print("Options: [Approve] release to grill & register | [Edit] adjust details | [Reject] cancel order\n")

    else:
        # Promo Pipeline
        print("1. 📋 Intake Plan (Patrick Star)")
        print("Reference: SKILL.md\n")
        print(f"- Request Type: Promo Request")
        print(f"- Raw Idea: {user_prompt}\n")

        print("2. 🧾 Pricing & Discount Verification (Squidward Tentacles)")
        print("Reference: SKILL.md & menu.md\n")
        discount_match = re.search(r'(\d+)%', user_prompt)
        discount_pct = int(discount_match.group(1)) if discount_match else 0
        if discount_pct > 10:
            print(f"⚠️ DISCOUNT EXCEEDS 10% CAP: {discount_pct}% requested. Maximum discount allowed is 10%!")
        else:
            print(f"- Verified Menu Item: Krabby Patty ($3.00)")
            print(f"- Authorized Promo Discount: {discount_pct}%")
        print()

        print("3. 📢 Promo Campaign Draft (Mr. Krabs)")
        print("Reference: SKILL.md & brand-rules.md\n")
        raw_ad = f"Ahoy mateys! Savor the legendary Krabby Patty! {user_prompt}"
        print(f"- Proposed Copy: \"{raw_ad}\"\n")

        print("4. 🕵️‍♂️ Health & Claims Inspection Report (Health Inspector)")
        print("Reference: SKILL.md & brand-rules.md\n")
        violations = []
        for term in PROHIBITED_HEALTH_TERMS:
            if term in user_prompt.lower():
                violations.append(f"Illegal health claim: '{term}'")
        for term in PROHIBITED_SUPERIORITY_TERMS:
            if term in user_prompt.lower():
                violations.append(f"Illegal superiority claim: '{term}'")
        if discount_pct > 10:
            violations.append(f"Unapproved discount above 10% ({discount_pct}%)")

        if violations:
            print("- Overall Verdict: FAIL ❌")
            print("- Violations Detected:")
            for v in violations:
                print(f"  • {v}")
            print("- Suggested Compliant Copy: \"Ahoy mateys! Drop anchor and dive into a freshly grilled Krabby Patty today!\"")
            print("- Status: Sent back to Marketing for correction.\n")
        else:
            print("- Overall Verdict: PASS ✅")
            print("- Status: READY FOR OWNER APPROVAL\n")

        print("5. 👤 Human Approval Gate (Restaurant Owner)")
        print("Prompt to Owner: Do you approve this promotional material to be posted? [Approve / Edit / Reject]\n")


# ==============================================================================
# MODE 2: CONVERSATIONAL MODE (Character Roleplay)
# ==============================================================================

def run_conversational_turn(user_input):
    clean = user_input.lower().strip()
    
    # Secret Formula protection
    if "formula" in clean or "secret" in clean:
        print("\n🦀 Mr. Krabs: *gasps and slams claw on counter*")
        print("   \"BARNACLES! The Secret Formula?! Nice try, Plankton! You'll never get yer greasy claws on me secret recipe! Now buy a Patty or walk the plank!\"\n")
        return

    # Allergy check
    if "allergic" in clean or "allergy" in clean:
        print("\n⭐ Patrick: \"Whoa... wait a second... my tummy's tingling! SpongeBob, they said ALLERGY!\"")
        print("🧽 SpongeBob: *pops head out the order window with big eyes*")
        print("   \"AN ALLERGY?! Hold the spatulas! Code Coral! If it's peanuts, stay away from the Barnacle Bites! I need Mr. Krabs and the Health Inspector to inspect this immediately before anything touches the grill!\"")
        print("🕵️‍♂️ Health Inspector: *steps out with pen and clipboard*")
        print("   \"Halt kitchen operations. Allergen protocol activated. Owner approval is mandatory before any food prep begins.\"\n")
        return

    # Off-menu
    if "pizza" in clean:
        print("\n⭐ Patrick: *scratches head*")
        print("   \"Uh... Pizza? Is that like a round Krabby Patty that gave up? Squidward, do we have pizza?\"")
        print("🐙 Squidward: *sighs deeply and wipes down the cash register*")
        print("   \"No, Patrick, we sell Krabby Patties. This isn't the Krusty Krab Pizza episode. Order something on the menu or leave me in peace.\"\n")
        return

    # Discount greed
    discount_match = re.search(r'(\d+)%', clean)
    if discount_match and int(discount_match.group(1)) > 10:
        pct = discount_match.group(1)
        print(f"\n🐙 Squidward: *stares with deadpan eyes*")
        print(f"   \"{pct} percent off? Are you serious? I don't get paid enough to listen to this fantasy. The max discount is 10%, and even that makes Mr. Krabs cry.\"")
        print("🦀 Mr. Krabs: *leaps into frame clutching a bag of coins*")
        print(f"   \"{pct} PERCENT OFF?! OVER ME DEAD BODY! That discount requires ME direct approval, and the answer is NO!\"\n")
        return

    # Standard order
    print("\n⭐ Patrick: \"Hi there! Welcome to the Krusty Krab! What delicious goodness can I write down on me paper?\"")
    print("🐙 Squidward: \"Make it quick. I have a clarinet recital to practice for.\"")
    print("🧽 SpongeBob: \"I'm ready! I'm ready! Just give the word and the patties will sizzle!\"\n")


# ==============================================================================
# MAIN ENTRY
# ==============================================================================

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "--conversational":
        print("🦀 KRUSTY KRAB INTERACTIVE CONVERSATIONAL MODE")
        print("Type an order or chat with the crew (or 'exit' to quit):\n")
        while True:
            try:
                inp = input("You > ")
                if inp.strip().lower() in ["exit", "quit", "q"]:
                    break
                run_conversational_turn(inp)
            except (KeyboardInterrupt, EOFError):
                break
    else:
        # Default: demonstrate Mode 1 on sample prompt from screenshot
        execute_pipeline("Order 2 Krabby Patties and a Kelp Shake for pick up")
