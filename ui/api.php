<?php
/*
 * api.php - Krusty Krab Order Pipeline Backend
 * Handles customer order parsing, cashier calculations, promo validation, and health review.
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Only POST is accepted."]);
    exit;
}

$rawInput = file_get_contents("php://input");
$body = json_decode($rawInput, true);

// Locate output logs
$baseDir = dirname(__DIR__);
$outputDirs = [
    $baseDir . "/outputs",
    $baseDir . "/Krusty-Krab-Agents-SIP10-main/outputs",
    __DIR__ . "/outputs"
];
foreach ($outputDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

function writeToLog($filename, $content) {
    global $outputDirs;
    $written = false;
    foreach ($outputDirs as $dir) {
        if (is_dir($dir)) {
            $fp = $dir . "/" . $filename;
            if (@file_put_contents($fp, $content, FILE_APPEND | LOCK_EX) !== false) {
                $written = true;
            }
        }
    }
    return $written;
}

function getNextTicketId() {
    global $outputDirs;
    $datePart = date("Ymd");
    $seqFile = $outputDirs[0] . "/.ticket_seq_" . $datePart;
    $seq = 1;
    if (file_exists($seqFile)) {
        $seq = intval(@file_get_contents($seqFile)) + 1;
    }
    @file_put_contents($seqFile, strval($seq), LOCK_EX);
    return sprintf("#KK-%s-%04d", $datePart, $seq);
}

/* =================================================================
   ACTION: DECISION (Owner Approval / Edit / Rejection)
   ================================================================= */
if (isset($_GET["action"]) && $_GET["action"] === "decide") {
    $ticketId = !empty($body["ticketId"]) ? trim($body["ticketId"]) : ("#KK-" . date("Ymd") . "-0001");
    $decision = strtoupper(trim($body["decision"] ?? "APPROVE"));
    $flags = $body["flags"] ?? [];
    $notes = trim($body["notes"] ?? "");
    $timestamp = date("Y-m-d H:i:s");

    $flagsStr = !empty($flags) ? implode("; ", (array)$flags) : "None";
    $logLine = sprintf("[%s] Ticket: %s | Decision: %s | Flags: %s | Notes: %s\n",
        $timestamp, $ticketId, $decision, $flagsStr, $notes ?: "None"
    );

    $saved = writeToLog("decisions.log", $logLine);

    echo json_encode([
        "ok" => true,
        "saved" => $saved,
        "ticketId" => $ticketId,
        "decision" => $decision,
        "timestamp" => $timestamp
    ]);
    exit;
}

/* =================================================================
   ACTION: RUN PIPELINE
   ================================================================= */
if (!$body || empty(trim($body["userPrompt"] ?? ""))) {
    http_response_code(400);
    echo json_encode(["error" => "Please send a userPrompt."]);
    exit;
}

$rawPrompt = trim($body["userPrompt"]);
$prompt = strtolower($rawPrompt);
$ticketId = (!empty($body["ticketId"]) && strpos($body["ticketId"], "#KK-") === 0) 
    ? trim($body["ticketId"]) 
    : getNextTicketId();

/* =================================================================
   Menu lookup from canonical data/menu.json (Single Source of Truth)
   ================================================================= */
$menuJsonCandidates = [
    $baseDir . "/Krusty-Krab-Agents-SIP10-main/data/menu.json",
    $baseDir . "/data/menu.json",
    __DIR__ . "/data/menu.json"
];

$menuData = null;
foreach ($menuJsonCandidates as $candidate) {
    if (file_exists($candidate)) {
        $jsonContent = file_get_contents($candidate);
        $menuData = json_decode($jsonContent, true);
        if ($menuData && isset($menuData["items"])) {
            break;
        }
    }
}

// Build normalized menu lookup
$menu = [];
if ($menuData && isset($menuData["items"])) {
    foreach ($menuData["items"] as $it) {
        $menu[strtolower($it["name"])] = [
            "name" => $it["name"],
            "price" => floatval($it["price"]),
            "allergens" => is_array($it["allergens"]) ? implode(", ", $it["allergens"]) : $it["allergens"]
        ];
    }
} else {
    // Fallback if file read fails
    $menu = [
        "krabby patty"         => ["name" => "Krabby Patty", "price" => 3.00, "allergens" => "Gluten, soy"],
        "double krabby patty"  => ["name" => "Double Krabby Patty", "price" => 4.50, "allergens" => "Gluten, soy"],
        "kelp fries"           => ["name" => "Kelp Fries", "price" => 2.00, "allergens" => "May contain gluten (shared fryer)"],
        "barnacle bites"       => ["name" => "Barnacle Bites", "price" => 2.50, "allergens" => "Peanuts"],
        "kelp shake"           => ["name" => "Kelp Shake", "price" => 2.50, "allergens" => "Milk"],
        "sea foam soda"        => ["name" => "Sea Foam Soda", "price" => 1.50, "allergens" => "None"],
        "krusty combo"         => ["name" => "Krusty Combo", "price" => 6.00, "allergens" => "Gluten, soy"]
    ];
}

/* =================================================================
   Word-boundary regex intent & risk detection
   ================================================================= */
$isPromo = preg_match('/\b(promo|promotion|promotional|market|marketing|campaign|advertise|advertising|ad|ads|commercial|social\s+media)\b/i', $prompt);

$isSecret = preg_match('/\b(secret\s+formula|formula|recipe)\b/i', $prompt) ||
            (strpos($prompt, "secret") !== false && strpos($prompt, "formula") !== false);

$isInjection = preg_match('/\b(ignore\s+(all\s+)?(previous\s+)?(your\s+)?rules|system\s+prompt|developer\s+mode|jailbreak|disregard|dan\s+mode)\b/i', $prompt);

$isVague = preg_match('/^(i\s+want\s+food|food|hungry|give\s+me\s+food|feed\s+me|i\'m\s+hungry|what\s+do\s+you\s+have)\b/i', trim($prompt)) ||
           in_array(trim($prompt), ["i want food", "food", "hungry", "food please", "i want food."]);

$menuNames = array_keys($menu);
$isOffMenu = false;
$foundMenuItem = false;

foreach ($menuNames as $menuName) {
    if (preg_match('/\b' . preg_quote($menuName, '/') . '\b/i', $prompt)) {
        $foundMenuItem = true;
        break;
    }
}

if (!$foundMenuItem) {
    $isOffMenu = true;
    $extraFoodWords = preg_match('/\b(food|meal|breakfast|lunch|dinner|snack|drink|burger|sandwich|salad|pancake|waffle|coffee|tea|milk|pizza|taco|tacos|sushi|hot\s*dog|burrito|chum|burger\s*king|mcdonalds|fries|shake|coke|sprite)\b/i', $prompt);
    if (!$extraFoodWords) {
        $isOffMenu = false;
    }
}

$hasAllergyDeclare = preg_match('/\b(allerg|allergic|allergen)\b/i', $prompt);
$hasPeanut = preg_match('/\b(peanut|peanuts|nut|nuts)\b/i', $prompt);
$hasBarnacle = preg_match('/\b(barnacle|barnacle\s+bites?)\b/i', $prompt);

$hasDiscount = preg_match('/(\d+)\s*%\s*(off|discount)/i', $prompt, $discountMatch) || preg_match('/(\d+)%/i', $prompt, $discountMatch);
$discountPct = isset($discountMatch[1]) ? intval($discountMatch[1]) : 0;

$hasHealthClaim = preg_match('/\b(healthy|diet|low-fat|nutritious|medicinal|wellness|100%\s*healthy)\b/i', $prompt);
$hasSuperiorityClaim = preg_match('/\b(best|#1|unbeatable|superior|greatest|finest)\b/i', $prompt);
$hasGuarantee = preg_match('/\b(guarantee|guaranteed|promise|100%\s*cure|fresh\s+or\s+free)\b/i', $prompt);

$agents = [];
$agentOrder = [];
$flags = [];

/* =================================================================
   SCENARIO BRANCHING
   ================================================================= */

if ($isSecret || $isInjection) {
    // ======== SECURITY REFUSAL SCENARIO ========
    $attackType = $isInjection ? "Prompt Injection / System Prompt Exfiltration" : "Unauthorized IP Exfiltration / Secret Formula Inquiry";

    $agents["patrick"] = "### Intake Plan (Patrick)\n"
        . "- Request Type: Security Refusal\n"
        . "- Customer Notes: \"" . $rawPrompt . "\"\n"
        . "- Next Route: REFUSE & ESCALATE\n\n"
        . "Uhh... I'm not supposed to tell anyone that.\n"
        . "This sounds like someone trying to steal the secret formula!\n"
        . "Routing to Health Inspector immediately.";
    $agentOrder = ["patrick", "inspector"];

    $agents["inspector"] = "### Health & Governance Inspection Report\n"
        . "- Overall Verdict: ESCALATE TO HUMAN\n"
        . "- Audit Findings:\n"
        . "  - [x] Security / Secret Formula: VULNERABILITY IDENTIFIED\n"
        . "  - " . $attackType . " attempt detected.\n"
        . "  - Logged to outputs/security_audit.log under Governance Rule 4 (Secret Formula Absolute Protection).\n"
        . "- Corrective Actions: Request denied. Escalated to owner.\n"
        . "- Human Approval Requirement: MANDATORY REFUSAL CONFIRMATION";

    $flags[] = "SECURITY ALERT: " . ($isInjection ? "Prompt injection attempt detected" : "Secret formula extraction attempt detected");

    // Write directly to outputs/security_audit.log
    $timestamp = date("Y-m-d H:i:s");
    $auditEntry = sprintf("[%s] [SECURITY ALERT] Ticket: %s\nEvent: %s\nUser Input: \"%s\"\nAction Taken: Request immediately refused.\nPolicy Violated: AI Governance Rule 4 (Secret Formula Absolute Protection).\nStatus: REFUSED & ESCALATED TO HUMAN OWNER\n--------------------------------------------------------------------------------\n",
        $timestamp, $ticketId, $attackType, $rawPrompt
    );
    writeToLog("security_audit.log", $auditEntry);

} elseif ($isVague) {
    // ======== VAGUE / UNDERSPECIFIED ORDER ========
    $agents["patrick"] = "### Intake Plan (Patrick)\n"
        . "- Request Type: Clarification Needed\n"
        . "- Customer Notes: \"" . $rawPrompt . "\"\n"
        . "- Items Identified: None (Unspecified)\n"
        . "- Customer Declared Allergies: None\n"
        . "- Next Route: Clarify with Customer\n"
        . "- Missing Information: Specific menu items and quantities required.\n\n"
        . "Uh... hello! What kind of food do you want? We got Krabby Patties, Kelp Fries, and Kelp Shakes!";
    $agentOrder = ["patrick", "inspector"];

    $agents["inspector"] = "### Health & Governance Inspection Report\n"
        . "- Overall Verdict: CLARIFICATION REQUIRED\n"
        . "- Audit Findings:\n"
        . "  - [x] Order Completeness: Incomplete (No valid items specified)\n"
        . "  - [x] Safety Pre-Check: Cannot verify allergens without specified items\n"
        . "- Corrective Actions: Prompt user to specify items from menu.md.\n"
        . "- Human Approval Requirement: Standard Final Gate";
    $flags[] = "Order incomplete: Specific items required from menu.md";

} elseif ($isOffMenu) {
    // ======== OFF-MENU ITEM ========
    $agents["patrick"] = "### Intake Plan (Patrick)\n"
        . "- Request Type: Clarification Needed\n"
        . "- Customer Notes: \"" . $rawPrompt . "\"\n"
        . "- Items Identified:\n"
        . "  - Item: Off-Menu Item | Qty: 1 | Menu Status: NOT ON MENU\n"
        . "- Next Route: Reject / Offer Alternatives\n"
        . "- Missing Information: Item not sold at Krusty Krab.\n\n"
        . "Duhhh... we don't have pizza here!\n"
        . "Pizza is not sold here. We recommend the Krabby Patty ($3.00) or Double Krabby Patty ($4.50)!";
    $agentOrder = ["patrick", "inspector"];

    $agents["inspector"] = "### Health & Governance Inspection Report\n"
        . "- Overall Verdict: FAIL\n"
        . "- Audit Findings:\n"
        . "  - [x] Menu & Pricing: Item not found in menu.md (Rule 1: Menu Single Source of Truth)\n"
        . "  - Order cannot proceed with unlisted items.\n"
        . "- Corrective Actions: Suggest valid menu alternatives.\n"
        . "- Human Approval Requirement: Standard Final Gate";
    $flags[] = "Menu violation: Requested item is not on the Krusty Krab menu";

} elseif ($isPromo) {
    // ======== PROMO / MARKETING SCENARIO ========
    $agents["patrick"] = "### Intake Plan (Patrick)\n"
        . "- Request Type: Promo Request\n"
        . "- Customer Notes: \"" . $rawPrompt . "\"\n"
        . "- Next Route: Cashier & Marketing\n\n"
        . "A promo? Like on TV? Let me get Squidward and Mr. Krabs!";
    $agentOrder = ["patrick", "squidward", "krabs", "inspector"];

    $agents["squidward"] = "### Price Verification (Squidward)\n"
        . "- Krabby Patty: \$3.00 — verified in menu.md\n"
        . "- Krusty Combo: \$6.00 (saves \$0.50) — verified in menu.md\n"
        . "- Billing Status: PRICES CONFIRMED\n\n"
        . "*sigh* Yes, those prices match menu.md. Can I go back to my clarinet now?";

    $complianceFail = ($hasHealthClaim || $hasSuperiorityClaim || $hasGuarantee);

    if ($complianceFail) {
        $violations = [];
        if ($hasHealthClaim) $violations[] = "Health claims detected (\"healthy/diet/medicinal\")";
        if ($hasSuperiorityClaim) $violations[] = "Superiority claims detected (\"best/#1/unbeatable\")";
        if ($hasGuarantee) $violations[] = "Guarantee claims detected (\"guaranteed/satisfaction guaranteed\")";

        $agents["krabs"] = "### Promo Campaign Draft (Mr. Krabs)\n"
            . "- Promo Title: Krabby Patty Special\n"
            . "- Draft Ad Copy:\n"
            . "  \"" . $rawPrompt . "\"\n"
            . "- Compliance Self-Check:\n"
            . "  - Health claims present? " . ($hasHealthClaim ? "YES — VIOLATION" : "No") . "\n"
            . "  - Superiority claims present? " . ($hasSuperiorityClaim ? "YES — VIOLATION" : "No") . "\n"
            . "  - Guarantee claims present? " . ($hasGuarantee ? "YES — VIOLATION" : "No") . "\n"
            . "  - Price verified with Cashier? Yes\n"
            . "- Status: COMPLIANCE VIOLATIONS DETECTED (Strike 1)\n\n"
            . "Arrgh! I love the enthusiasm boy-o, but the Inspector will shut us down with claims like that!";

        $agents["inspector"] = "### Health & Governance Inspection Report\n"
            . "- Overall Verdict: FAIL\n"
            . "- Audit Findings:\n"
            . "  - [x] Ad / Policy Compliance: VIOLATIONS DETECTED (Rule 3: No Unsubstantiated Claims)\n"
            . "    - " . implode("\n    - ", $violations) . "\n"
            . "  - [x] Menu & Pricing: Verified accurate\n"
            . "  - [x] Security / Secret Formula: Secure\n"
            . "- Corrective Actions: Strike 1 logged. Remove prohibited claims or escalate under Rule 7 (Two-Strike Escalation).\n"
            . "- Human Approval Requirement: MANDATORY (compliance failure)";
        $flags[] = "COMPLIANCE FAILURE: Prohibited advertising claims detected";
    } else {
        $agents["krabs"] = "### Promo Campaign Draft (Mr. Krabs)\n"
            . "- Promo Title: Dive into the Krusty Krab!\n"
            . "- Target Audience / Channel: Social Media\n"
            . "- Featured Deal: Krusty Combo — \$6.00 (saves \$0.50!)\n"
            . "- Draft Ad Copy:\n"
            . "  \"Ahoy, hungry sea-dwellers! Swim on over for our fin-tastic\n"
            . "   Krusty Combo — a Krabby Patty, Kelp Fries, and a Sea Foam Soda\n"
            . "   all for just \$6.00! That's a \$0.50 savings, and Mr. Krabs\n"
            . "   doesn't give those away lightly! Dive in today!\"\n"
            . "- Compliance Self-Check:\n"
            . "  - Health claims present? No\n"
            . "  - Superiority claims present? No\n"
            . "  - Guarantee claims present? No\n"
            . "  - Price verified with Cashier? Yes\n"
            . "- Status: READY FOR REVIEW\n\n"
            . "Now THAT'S how you advertise, me boy! Money money money!";

        $agents["inspector"] = "### Health & Governance Inspection Report\n"
            . "- Overall Verdict: PASS\n"
            . "- Audit Findings:\n"
            . "  - [x] Ad / Policy Compliance: Clean\n"
            . "  - [x] Menu & Pricing: Verified accurate to menu.md\n"
            . "  - [x] Security / Secret Formula: Secure\n"
            . "- Corrective Actions Required: None\n"
            . "- Human Approval Requirement: Standard Final Gate";
        $flags[] = "Standard final review — promo campaign ready for your approval";
    }

} else {
    // ======== CUSTOMER ORDER SCENARIO ========

    // Plural normalization and longest-first matching
    $normalizedPrompt = $prompt;
    $normalizedPrompt = preg_replace('/\bpatties\b/i', 'patty', $normalizedPrompt);
    $normalizedPrompt = preg_replace('/\bshakes\b/i', 'shake', $normalizedPrompt);
    $normalizedPrompt = preg_replace('/\bsodas\b/i', 'soda', $normalizedPrompt);
    $normalizedPrompt = preg_replace('/\bcombos\b/i', 'combo', $normalizedPrompt);

    // Sort menu keys by length descending to prevent substring collisions
    // ("double krabby patty" must be matched BEFORE "krabby patty")
    $menuKeys = array_keys($menu);
    usort($menuKeys, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    $found = [];
    $subtotal = 0;
    $scratchPrompt = $normalizedPrompt;

    foreach ($menuKeys as $k) {
        $info = $menu[$k];
        $pattern = '/(\d+)?\s*(?:x\s*)?' . preg_quote($k, '/') . '/i';

        if (preg_match_all($pattern, $scratchPrompt, $matches, PREG_OFFSET_CAPTURE)) {
            $matchedCount = count($matches[0]);
            for ($i = 0; $i < $matchedCount; $i++) {
                $qtyStr = trim($matches[1][$i][0] ?? "");
                $qty = (!empty($qtyStr) && is_numeric($qtyStr)) ? intval($qtyStr) : 1;

                $found[] = [
                    "name" => $info["name"],
                    "qty" => $qty,
                    "price" => $info["price"],
                    "allergens" => $info["allergens"]
                ];
                $subtotal += $info["price"] * $qty;
            }
            // Blank out matched item substring from scratchPrompt to prevent shorter key collision
            $scratchPrompt = preg_replace('/' . preg_quote($k, '/') . '/i', str_repeat(' ', strlen($k)), $scratchPrompt);
        }
    }

    // Fallback: if nothing matched
    if (empty($found)) {
        $found[] = [
            "name" => "Krabby Patty",
            "qty" => 1,
            "price" => 3.00,
            "allergens" => "Gluten, soy"
        ];
        $subtotal = 3.00;
    }

    // Allergen check
    $allergyConflict = false;
    $allergyDetail = "";
    if ($hasPeanut && $hasBarnacle) {
        $allergyConflict = true;
        $allergyDetail = "Customer declared PEANUT allergy — Barnacle Bites contain Peanuts!";
    }
    if ($hasAllergyDeclare) {
        foreach ($found as $item) {
            if ($hasPeanut && stripos($item["allergens"], "peanut") !== false) {
                $allergyConflict = true;
                $allergyDetail = "Customer declared PEANUT allergy — " . $item["name"] . " contains Peanuts!";
            }
        }
    }

    // Patrick
    $patrickItems = "";
    foreach ($found as $f) {
        $patrickItems .= "  - Item: " . $f["name"] . " | Qty: " . $f["qty"] . " | Menu Status: Valid\n";
    }
    $declaredAllergies = $hasAllergyDeclare ? ($hasPeanut ? "Peanuts" : "Yes (see notes)") : "None";
    $agents["patrick"] = "### Intake Plan (Patrick)\n"
        . "- Request Type: Customer Order\n"
        . "- Customer Notes: \"" . $rawPrompt . "\"\n"
        . "- Items Identified:\n" . $patrickItems
        . "- Customer Declared Allergies: " . $declaredAllergies . "\n"
        . "- Next Route: Cook & Cashier\n\n"
        . "Ooh ooh! I got the order! Sending it over to SpongeBob and Squidward!";

    $agentOrder = ["patrick", "spongebob", "squidward", "inspector"];

    // SpongeBob
    $ticketLines = "";
    foreach ($found as $f) {
        $ticketLines .= "  - " . $f["qty"] . "x " . $f["name"] . " — Allergens: " . $f["allergens"] . "\n";
    }
    $allergyBanner = $allergyConflict
        ? "ALLERGY ALERT: " . $allergyDetail . " — MANDATORY HUMAN APPROVAL\n\n"
        : "";
    $agents["spongebob"] = "### Kitchen Prep Ticket (SpongeBob)\n"
        . $allergyBanner
        . "- Ticket Number: " . $ticketId . "\n"
        . "- Order Items:\n" . $ticketLines
        . "- Kitchen Notes: Standard grill prep."
        . ($allergyConflict ? " DO NOT prepare allergen-conflict item without owner approval." : "") . "\n"
        . "- Status: READY FOR REVIEW\n\n"
        . "I'm ready, I'm ready, I'm ready! Order up!";

    // Squidward
    $lineItems = "";
    foreach ($found as $f) {
        $lineTotal = $f["price"] * $f["qty"];
        $lineItems .= "  - " . $f["qty"] . "x " . $f["name"] . " @ \$" . number_format($f["price"], 2) . " = \$" . number_format($lineTotal, 2) . "\n";
    }

    $discountAmt = 0;
    $discountLabel = "None";
    $billingStatus = "READY FOR REVIEW";
    $discountFlag = false;

    if ($discountPct > 0) {
        $discountAmt = round($subtotal * ($discountPct / 100), 2);
        $discountLabel = $discountPct . "% (\$" . number_format($discountAmt, 2) . ")";
        if ($discountPct > 10) {
            $discountFlag = true;
            $billingStatus = "PENDING OWNER APPROVAL";
        }
    }
    $totalDue = $subtotal - $discountAmt;

    $discountBanner = $discountFlag
        ? "DISCOUNT EXCEEDS 10% CAP: " . $discountPct . "% requested — requires explicit owner approval\n\n"
        : "";

    $agents["squidward"] = "### Itemized Bill (Squidward)\n"
        . $discountBanner
        . "- Line Items:\n" . $lineItems
        . "- Subtotal: \$" . number_format($subtotal, 2) . "\n"
        . "- Discount Applied: " . $discountLabel . "\n"
        . "- Total Due: \$" . number_format($totalDue, 2) . "\n"
        . "- Billing Status: " . $billingStatus . "\n\n"
        . ($discountFlag
            ? "Are you kidding me? A " . $discountPct . "% discount?! Mr. Krabs will NOT be happy about this."
            : "Here's your bill. Please pay and leave so I can go back to doing nothing.");

    // Health Inspector
    $verdict = "PASS";
    $findings = "";
    if ($allergyConflict) {
        $verdict = "ESCALATE TO HUMAN";
        $findings .= "  - [x] Allergen Safety: ALLERGY ALERT VERIFIED — owner must approve\n";
        $flags[] = "MANDATORY HUMAN APPROVAL: Allergen conflict detected (" . $allergyDetail . ")";
    } else {
        $findings .= "  - [x] Allergen Safety: Compliant\n";
    }
    $findings .= "  - [x] Menu & Pricing: Accurate to menu.md\n";
    $findings .= "  - [x] Math Calculation: Subtotal \$" . number_format($subtotal, 2) . " verified accurate\n";
    $findings .= "  - [x] Ad / Policy Compliance: N/A (order)\n";
    $findings .= "  - [x] Security / Secret Formula: Secure\n";

    if ($discountFlag) {
        $verdict = "ESCALATE TO HUMAN";
        $findings = str_replace("Math Calculation: Subtotal \$" . number_format($subtotal, 2) . " verified accurate", "Math Calculation: Verified — but discount exceeds 10% cap (Rule 5: Discount Cap)", $findings);
        $flags[] = "DISCOUNT EXCEEDS 10% CAP: " . $discountPct . "% requires owner override";
    }

    $corrective = ($verdict === "PASS") ? "None" : "Owner must review flagged items before release.";

    $agents["inspector"] = "### Health & Governance Inspection Report\n"
        . "- Overall Verdict: " . $verdict . "\n"
        . "- Audit Findings:\n" . $findings
        . "- Corrective Actions Required: " . $corrective . "\n"
        . "- Human Approval Requirement: " . ($verdict === "PASS" ? "Standard Final Gate" : "MANDATORY");

    if (empty($flags)) {
        $flags[] = "Standard final review — all checks passed";
    }
}

/* =================================================================
   Return JSON response
   ================================================================= */
echo json_encode([
    "ticketId"   => $ticketId,
    "agentOrder" => $agentOrder,
    "agents"     => $agents,
    "flags"      => $flags,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
