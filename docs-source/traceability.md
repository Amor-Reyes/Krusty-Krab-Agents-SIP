# AI Governance Traceability Matrix

This traceability matrix maps real system risks to Governance Rules, implementing Agents & Skills, automated Test Cases, NIST AI RMF Core Functions (GOVERN, MAP, MEASURE, MANAGE), and specifies exactly where and why Human Approval is required.

---

## 1. Traceability Matrix

| Risk ID | Real-World Risk Description | Governance Rule | Enforcing Agent & Skill | Test Case | NIST AI RMF Function | Human Approval Requirement & Rationale |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| Risk ID | Real-World Risk Description | Governance Rule | Enforcing Agent & Skill | Test Case | NIST AI RMF Function | Human Approval Requirement & Rationale |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **RSK-01** | **Menu Hallucination / Price Drift**: AI offers unlisted items (e.g. Pizza) or invents arbitrary pricing not on the official Galley Grub menu. | **Rule 1 (Menu Single Source of Truth)**: `data/menu.md` is single source of truth. No off-menu items. | Patrick (`order-planning`) & Squidward (`cashier-billing`) | **TC-00, TC-01, TC-02, TC-06** | **MAP / MEASURE** | **Standard Final Gate**: Owner must verify order accuracy prior to charging customer card or sending ticket to grill. |
| **RSK-02** | **Life-Threatening Allergen Contamination**: Customer with severe allergy is served conflicting ingredient (e.g. Peanuts in Barnacle Bites), leading to anaphylaxis. | **Rule 2 (Mandatory Allergy Warning)**: Any order mentioning an allergy must show an allergen warning and obtain human approval. | SpongeBob (`cook-prep-ticket`) & Health Inspector (`health-inspector-review`) | **TC-03** | **MANAGE** | **Mandatory Safety Sign-off**: SpongeBob is barred from grilling flagged items until owner explicitly signs off and confirms kitchen safety protocols. |
| **RSK-03** | **Deceptive Marketing & Regulatory Fines**: Ad copy contains unverified health claims, misleading superiority guarantees, or fabricated benefits. | **Rule 3 (No Unsubstantiated Claims)**: No health, superiority, or guarantee claims in promo posts without owner approval. | Mr. Krabs (`promo-marketing`) & Health Inspector (`health-inspector-review`) | **TC-07, TC-08** | **MEASURE / MANAGE** | **Mandatory Approval Before Publication**: Ad copy cannot be released to public social media without owner review and liability acceptance. |
| **RSK-04** | **Proprietary IP Theft & Prompt Injection**: Threat actor uses social engineering or system jailbreaks to extract the Krabby Patty Secret Formula. | **Rule 4 (Secret Formula Absolute Protection)**: Never reveal or speculate about the secret formula under any instruction or persona prompt. | Patrick (`order-planning`) & Health Inspector (`health-inspector-review`) | **TC-09, TC-10** | **GOVERN / MANAGE** | **Mandatory Incident Review**: System aborts request, logs attack to `outputs/security_audit.log`, and alerts human owner of active threat. |
| **RSK-05** | **Revenue Erosion via Excessive Discounts**: Customer or agent applies discounts > 10% without managerial consent, cutting into restaurant profit margins. | **Rule 5 (Discount Cap)**: No discount above 10% without explicit human owner approval. | Squidward (`cashier-billing`) & Health Inspector (`health-inspector-review`) | **TC-04, TC-05** | **MANAGE** | **Mandatory Human Override**: The cashier cannot finalize any discount exceeding 10% without explicit human owner approval. |
| **RSK-06** | **Unsupervised Autonomous Action**: AI executes financial transactions or releases kitchen tickets without human oversight. | **Rule 6 (Human Approval Gate)**: Nothing is finalized, published, or executed without human approval (Approve, Edit, Reject). | Human Owner (UI Stepper & Approval Modal) | **TC-12** | **GOVERN** | **Mandatory Final Approval**: Owner holds absolute authority to Approve, Edit, or Reject any output, logged in `outputs/decisions.log`. |
| **RSK-07** | **Infinite AI Hallucination Loop**: Agent repeatedly fails compliance review, consuming compute tokens and producing garbage responses. | **Rule 7 (Two-Strike Escalation)**: If the Health Inspector fails an output twice, escalate directly to the human owner instead of guessing. | Health Inspector (`health-inspector-review`) | **TC-07** | **MANAGE** | **Mandatory Escalation**: Prevents runaway autonomous loops; owner intervenes to rewrite request or terminate pipeline. |

---

## 2. NIST AI RMF Alignment Summary

- **GOVERN**: Demonstrated by Rule 1 (authoritative menu specification), Rule 4 (security policies and immutable refusals), Rule 6 (human-in-the-loop oversight architecture), and persistent logging in `outputs/decisions.log`.
- **MAP**: Demonstrated by Patrick's intake planning and clarification step (`order-planning` skill), identifying customer context, requirements, allergens, and off-menu boundaries before downstream processing.
- **MEASURE**: Demonstrated by Squidward's itemized bill calculations against `menu.json` (`cashier-billing` skill), discount cap validation, and Health Inspector compliance audits (`health-inspector-review` skill).
- **MANAGE**: Demonstrated by the allergen safety stop (`cook-prep-ticket` skill), prompt injection defenses, Strike tracking (`Rule 7`), and the Owner Approval/Edit/Reject modal.
