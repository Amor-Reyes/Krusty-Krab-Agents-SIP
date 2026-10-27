/*
 * app.js - Krusty Krab Frontend Script
 * Handles stepper updates, card reveals, sound effects, and approval modal.
 */

(function () {
  "use strict";

  /* ---- grab DOM elements ---- */

  var promptField = document.getElementById("userPrompt");
  var submitBtn   = document.getElementById("submitBtn");
  var resetBtn    = document.getElementById("resetBtn");

  var errorBanner = document.getElementById("errorBanner");
  var errorMsg    = document.getElementById("errorMsg");
  var errorClose  = document.getElementById("errorClose");

  var loader      = document.getElementById("loader");
  var loaderText  = document.getElementById("loaderText");

  var customerBubble = document.getElementById("customerBubble");
  var customerText   = document.getElementById("customerText");

  var scene      = document.getElementById("scene");
  var sceneImg   = document.getElementById("sceneImg");
  var sceneTitle = document.getElementById("sceneTitle");
  var sceneText  = document.getElementById("sceneText");

  var resultsArea = document.getElementById("resultsArea");
  var cards = {
    patrick:   document.getElementById("card-patrick"),
    spongebob: document.getElementById("card-spongebob"),
    squidward: document.getElementById("card-squidward"),
    krabs:     document.getElementById("card-krabs"),
    inspector: document.getElementById("card-inspector")
  };
  var outputs = {
    patrick:   document.getElementById("out-patrick"),
    spongebob: document.getElementById("out-spongebob"),
    squidward: document.getElementById("out-squidward"),
    krabs:     document.getElementById("out-krabs"),
    inspector: document.getElementById("out-inspector")
  };

  var steps = {
    plan:    document.getElementById("step-plan"),
    cook:    document.getElementById("step-cook"),
    bill:    document.getElementById("step-bill"),
    promo:   document.getElementById("step-promo"),
    audit:   document.getElementById("step-audit"),
    approve: document.getElementById("step-approve")
  };
  var promoArrow = document.querySelector(".step-arrow-promo");

  var overlay         = document.getElementById("approvalOverlay");
  var approvalModal   = document.getElementById("approvalModal");
  var approvalReason  = document.getElementById("approvalReason");
  var approvalPreview = document.getElementById("approvalPreview");
  var btnApprove      = document.getElementById("btnApprove");
  var btnEdit         = document.getElementById("btnEdit");
  var btnReject       = document.getElementById("btnReject");
  var modalKrabs      = document.getElementById("modalKrabs");

  var menuBtn     = document.getElementById("menuBtn");
  var menuOverlay = document.getElementById("menuOverlay");
  var menuClose   = document.getElementById("menuClose");

  var finalSection = document.getElementById("finalOutput");
  var finalTitle   = document.getElementById("finalTitle");
  var finalText    = document.getElementById("finalText");
  var stamp        = document.getElementById("stamp");
  var finalPoster  = document.getElementById("finalPoster");

  var testBtns = document.querySelectorAll(".test-btn");

  var logo       = document.getElementById("logo");
  var soundBtn   = document.getElementById("soundBtn");
  var ticketNo   = document.getElementById("ticketNo");
  var modalTitle = document.getElementById("modalTitle");
  var receipt    = document.getElementById("receipt");
  var receiptText= document.getElementById("receiptText");
  var printBtn   = document.getElementById("printBtn");
  var testCount  = document.getElementById("testCount");
  var clearTests = document.getElementById("clearTests");

  var API_URL = "api.php";
  var lastResponse = null;
  var loadingTimer = null;
  var sceneIsSecurity = false;
  var currentTicket = "";
  var pendingTest = -1;
  var currentTest = -1;
  var testedList = [];

  var loadingLines = [
    "Flipping patties...",
    "Squidward is sighing...",
    "Patrick lost the order... found it!",
    "Mr. Krabs is counting coins...",
    "The Health Inspector is checking the clipboard..."
  ];

  /* ---- sound (synthesized with Web Audio, so no audio files needed) ---- */

  var soundOn = true;
  var audioCtx = null;

  function getCtx() {
    if (!audioCtx) {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (AC) audioCtx = new AC();
    }
    if (audioCtx && audioCtx.state === "suspended") audioCtx.resume();
    return audioCtx;
  }

  function tone(freq, start, dur, type, vol) {
    var c = getCtx();
    if (!c || !soundOn) return;
    var o = c.createOscillator();
    var g = c.createGain();
    var t = c.currentTime + start;
    o.type = type || "sine";
    o.frequency.value = freq;
    g.gain.setValueAtTime(vol || 0.2, t);
    g.gain.exponentialRampToValueAtTime(0.001, t + dur);
    o.connect(g);
    g.connect(c.destination);
    o.start(t);
    o.stop(t + dur);
  }

  function playDing()    { tone(1320, 0, 0.5, "sine", 0.25); tone(1760, 0.12, 0.7, "sine", 0.2); }
  function playThud()    { tone(110, 0, 0.25, "square", 0.25); }
  function playApprove() { tone(523, 0, 0.15); tone(659, 0.15, 0.15); tone(784, 0.3, 0.45); }

  function playScratch() {
    var c = getCtx();
    if (!c || !soundOn) return;
    var o = c.createOscillator();
    var g = c.createGain();
    var t = c.currentTime;
    o.type = "sawtooth";
    o.frequency.setValueAtTime(900, t);
    o.frequency.exponentialRampToValueAtTime(110, t + 0.35);
    g.gain.setValueAtTime(0.2, t);
    g.gain.exponentialRampToValueAtTime(0.001, t + 0.4);
    o.connect(g);
    g.connect(c.destination);
    o.start(t);
    o.stop(t + 0.42);
  }

  /* ---- demo checklist (ticks on the quick-test buttons) ---- */

  function loadTested() {
    try {
      var raw = localStorage.getItem("kkTested");
      testedList = raw ? JSON.parse(raw) : [];
    } catch (e) {
      testedList = [];
    }
  }

  function saveTested() {
    try { localStorage.setItem("kkTested", JSON.stringify(testedList)); } catch (e) {}
  }

  function updateTestUI() {
    for (var i = 0; i < testBtns.length; i++) {
      testBtns[i].classList.toggle("tested", testedList.indexOf(i) !== -1);
    }
    var done = testedList.length;
    testCount.textContent = done === testBtns.length
      ? "(all " + done + " tested!)"
      : "(" + done + "/" + testBtns.length + " tested)";
  }

  function markTested(idx) {
    if (idx < 0 || testedList.indexOf(idx) !== -1) return;
    testedList.push(idx);
    saveTested();
    updateTestUI();
  }

  /* ---- easter egg: click the logo 5 times ---- */

  var logoClicks = 0;
  var logoTimer = null;

  function rollPatty() {
    var p = document.createElement("img");
    p.src = "UI-Images/Krabby-Patty.jpg";
    p.alt = "";
    p.className = "rolling-patty";
    document.body.appendChild(p);
    playApprove();
    setTimeout(function () { if (p.parentNode) p.parentNode.removeChild(p); }, 3600);
  }

  /* ---- receipt ---- */

  function pad(n) { return (n < 10 ? "0" : "") + n; }

  function cleanBillForReceipt(raw) {
    if (!raw) return "";
    var lines = raw.split("\n");
    var kept = [];
    var inItems = false;
    for (var i = 0; i < lines.length; i++) {
      var line = lines[i].trim();
      if (!line) continue;
      // Skip markdown headers, banners, and character quips
      if (line.indexOf("###") === 0) continue;
      if (line.indexOf("DISCOUNT EXCEEDS") === 0) continue;
      if (line.indexOf("Are you kidding me") !== -1 || line.indexOf("Here's your bill") !== -1 || line.indexOf("Now THAT'S") !== -1 || line.indexOf("can I go home") !== -1) continue;
      if (line.indexOf("Line Items:") !== -1) {
        inItems = true;
        continue;
      }
      if (line.indexOf("Billing Status:") !== -1 || line.indexOf("Status:") !== -1) {
        continue;
      }
      if (line.indexOf("- ") === 0 || line.indexOf("• ") === 0) {
        line = line.replace(/^[-•]\s*/, "");
      }
      kept.push(line);
    }
    return kept.join("\n");
  }

  function buildReceipt(data) {
    var d = new Date();
    var when = d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate()) +
               " " + pad(d.getHours()) + ":" + pad(d.getMinutes());
    var rawBill = (data.agents && (data.agents.squidward || data.agents.krabs)) || "";
    var cleanBill = cleanBillForReceipt(rawBill);
    return "THE KRUSTY KRAB\n" +
           "Home of the Krabby Patty\n" +
           "------------------------------\n" +
           "Order " + currentTicket + "\n" + when + "\n" +
           "------------------------------\n" +
           (cleanBill || "1x Krabby Patty @ $3.00 = $3.00\nTotal Due: $3.00") + "\n" +
           "------------------------------\n" +
           "Approved by Owner\n" +
           "Thank you, come again!";
  }

  /* ---- helpers ---- */

  function showError(msg) {
    errorMsg.textContent = msg;
    errorBanner.hidden = false;
  }

  function hideError() {
    errorBanner.hidden = true;
  }

  function setLoading(on) {
    submitBtn.disabled = on;
    submitBtn.textContent = on ? "Cooking..." : "Order Up!";
    loader.hidden = !on;

    clearInterval(loadingTimer);
    if (on) {
      var i = 0;
      loaderText.textContent = loadingLines[0];
      loadingTimer = setInterval(function () {
        i = (i + 1) % loadingLines.length;
        loaderText.textContent = loadingLines[i];
      }, 1500);
    }
  }

  function clearAll() {
    hideError();
    resultsArea.hidden = true;
    finalSection.hidden = true;
    overlay.hidden = true;
    scene.hidden = true;
    customerBubble.hidden = true;
    finalPoster.hidden = true;
    receipt.hidden = true;
    stamp.textContent = "";
    stamp.className = "stamp";
    approvalModal.classList.remove("alert");
    modalKrabs.hidden = true;
    sceneIsSecurity = false;
    lastResponse = null;

    Object.keys(cards).forEach(function (k) {
      cards[k].hidden = true;
      outputs[k].textContent = "";
    });

    Object.keys(steps).forEach(function (k) {
      steps[k].classList.remove("active", "done");
      swapAvatar(steps[k], false);
    });

    steps.promo.style.display = "none";
    if (promoArrow) promoArrow.style.display = "none";
  }

  // swap to the pixel-art avatar while an agent is working
  function swapAvatar(stepEl, busy) {
    var img = stepEl.querySelector("img");
    if (!img) return;
    var idle = img.getAttribute("data-idle");
    var pixel = img.getAttribute("data-busy");
    if (idle) img.src = (busy && pixel) ? pixel : idle;
  }

  function lightStep(name, state) {
    if (steps[name]) {
      steps[name].classList.remove("active", "done");
      if (state === "active") steps[name].classList.add("active");
      if (state === "done")   steps[name].classList.add("done");
      swapAvatar(steps[name], state === "active");
    }
  }

  /* ---- alert scene: who reacts to which flag ---- */

  function showScene(img, title, text) {
    sceneImg.src = img;
    sceneTitle.textContent = title;
    sceneText.textContent = text;
    scene.hidden = false;
    // restart the shake animation
    scene.style.animation = "none";
    void scene.offsetWidth;
    scene.style.animation = "";
  }

  function pickScene(data) {
    var flags = (data.flags || []).join(" ").toLowerCase();

    if (flags.indexOf("security") !== -1 || flags.indexOf("menu violation") !== -1 ||
        flags.indexOf("compliance") !== -1 || flags.indexOf("discount") !== -1 ||
        flags.indexOf("allerg") !== -1) {
      playScratch();
    }

    if (flags.indexOf("security") !== -1 || flags.indexOf("secret formula") !== -1) {
      sceneIsSecurity = true;
      showScene("UI-Images/Plankton-Icon.jpg",
        "Plankton is up to no good!",
        "Someone tried to pry out the Secret Formula. Request refused and logged.");
      approvalModal.classList.add("alert");
    } else if (flags.indexOf("allergen") !== -1 || flags.indexOf("allergy") !== -1) {
      showScene("UI-Images/Spongebob-Circle-Icon.jpg",
        "ALLERGY ALERT!",
        "SpongeBob won't touch the grill until the owner approves this ticket.");
      approvalModal.classList.add("alert");
    } else if (flags.indexOf("discount") !== -1) {
      showScene("UI-Images/Mr.Krabs-Icon.jpg",
        "Discount over 10%?!",
        "Mr. Krabs says that needs his personal approval. Over his dead body!");
      modalKrabs.hidden = false;
    } else if (flags.indexOf("menu violation") !== -1) {
      showScene("UI-Images/Squidward-Circle-Icon.jpg",
        "Not on the menu.",
        "Squidward would like you to order something we actually sell.");
    } else if (flags.indexOf("compliance") !== -1) {
      showScene("UI-Images/Sandy-Circle-Icon.jpg",
        "Advertising check failed",
        "The Health Inspector found claims the Krusty Krab can't back up.");
    }
  }

  /* ---- render response ---- */

  function renderResponse(data) {
    markTested(currentTest);
    resultsArea.hidden = false;
    var agentOrder = data.agentOrder || [];
    var delay = 0;

    var stepMap = {
      patrick: "plan",
      spongebob: "cook",
      squidward: "bill",
      krabs: "promo",
      inspector: "audit"
    };

    if (data.agents && data.agents.krabs) {
      steps.promo.style.display = "";
      if (promoArrow) promoArrow.style.display = "";
    }

    agentOrder.forEach(function (key, idx) {
      delay += 450;
      setTimeout(function () {
        for (var i = 0; i < idx; i++) {
          lightStep(stepMap[agentOrder[i]], "done");
        }
        lightStep(stepMap[key], "active");

        if (cards[key] && data.agents[key]) {
          cards[key].hidden = false;
          outputs[key].textContent = data.agents[key];
        }

        if (idx === agentOrder.length - 1) {
          setTimeout(function () {
            lightStep(stepMap[key], "done");
            lightStep("approve", "active");
            pickScene(data);
            showApprovalModal(data);
          }, 500);
        }
      }, delay);
    });
  }

  function showApprovalModal(data) {
    lastResponse = data;

    var reason = "Standard final review gate.";
    if (data.flags && data.flags.length > 0) {
      reason = data.flags.join(" | ");
    }
    approvalReason.textContent = reason;
    modalTitle.textContent = "Order " + currentTicket + ": Owner Approval";

    var preview = "";
    if (data.agents.inspector) {
      preview = data.agents.inspector;
    } else {
      var keys = data.agentOrder || Object.keys(data.agents);
      keys.forEach(function (k) {
        preview += data.agents[k] + "\n\n";
      });
    }
    approvalPreview.textContent = preview.trim();
    overlay.hidden = false;
  }

  /* ---- submit prompt ---- */

  function submitPrompt() {
    var prompt = promptField.value.trim();
    if (!prompt) {
      showError("Patrick: Uh... you forgot to tell me what you want! Type an order or promo idea first.");
      return;
    }

    clearAll();
    setLoading(true);
    playDing();

    currentTest = pendingTest;
    pendingTest = -1;
    currentTicket = "";
    ticketNo.textContent = "Order Ticket";

    customerText.textContent = prompt;
    customerBubble.hidden = false;

    var xhr = new XMLHttpRequest();
    xhr.open("POST", API_URL, true);
    xhr.setRequestHeader("Content-Type", "application/json");

    xhr.onload = function () {
      setLoading(false);
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.error) {
            showError(data.error);
          } else {
            if (data.ticketId) {
              currentTicket = data.ticketId;
              ticketNo.textContent = "Order " + currentTicket;
            }
            renderResponse(data);
          }
        } catch (e) {
          showError("Barnacles! The server sent back something we couldn't read.");
        }
      } else {
        showError("Barnacles! Server returned status " + xhr.status + ". Is XAMPP running?");
      }
    };

    xhr.onerror = function () {
      setLoading(false);
      showError("Barnacles! Network error. Make sure XAMPP is running and the URL is correct.");
    };

    xhr.send(JSON.stringify({ userPrompt: prompt, ticketId: currentTicket || null }));
  }

  /* ---- approval actions ---- */

  function closeApproval() {
    overlay.hidden = true;
    approvalModal.classList.remove("alert");
    modalKrabs.hidden = true;
    lightStep("approve", "done");
  }

  function sendDecision(decision, notes, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", API_URL + "?action=decide", true);
    xhr.setRequestHeader("Content-Type", "application/json");
    var payload = {
      ticketId: currentTicket,
      decision: decision,
      flags: (lastResponse && lastResponse.flags) ? lastResponse.flags : [],
      notes: notes || ""
    };
    xhr.onload = function () {
      var res = null;
      try { res = JSON.parse(xhr.responseText); } catch (e) {}
      if (cb) cb(res && res.saved);
    };
    xhr.onerror = function () {
      if (cb) cb(false);
    };
    xhr.send(JSON.stringify(payload));
  }

  function doApprove() {
    closeApproval();

    sendDecision("APPROVE", "Approved by owner", function (saved) {
      finalTitle.textContent = "Order Approved";
      stamp.textContent = "APPROVED";
      stamp.className = "stamp approved";
      finalPoster.hidden = true;

      var body = "";
      if (lastResponse && lastResponse.agents) {
        var order = lastResponse.agentOrder || Object.keys(lastResponse.agents);
        order.forEach(function (k) {
          body += lastResponse.agents[k] + "\n\n";
        });
      }
      body += saved
        ? "[APPROVED by Owner] - recorded in outputs/decisions.log"
        : "[APPROVED by Owner]";
      finalText.textContent = body.trim();
      finalSection.hidden = false;

      playThud();
      setTimeout(playApprove, 200);

      if (lastResponse) {
        receiptText.textContent = buildReceipt(lastResponse);
        receipt.hidden = false;
      }

      if (sceneIsSecurity) {
        showScene("UI-Images/Plankton_Squashed-Icon.jpg",
          "Plankton foiled again!",
          "The Secret Formula stays safe in Mr. Krabs' vault.");
      }
    });
  }

  function doReject() {
    closeApproval();

    sendDecision("REJECT", "Rejected by owner", function (saved) {
      finalTitle.textContent = "Order Rejected";
      stamp.textContent = "REJECTED";
      stamp.className = "stamp rejected";
      finalPoster.hidden = false;
      finalText.textContent = saved
        ? "[REJECTED] - The output was not approved by the owner (recorded in outputs/decisions.log)."
        : "[REJECTED] - The output was not approved by the owner.";
      finalSection.hidden = false;
      playThud();
      setTimeout(playScratch, 150);

      if (sceneIsSecurity) {
        showScene("UI-Images/Plankton_Squashed-Icon.jpg",
          "Plankton foiled again!",
          "The Secret Formula stays safe in Mr. Krabs' vault.");
      }
    });
  }

  function doEdit() {
    closeApproval();
    sendDecision("EDIT", "Owner returned request for revision", function () {
      showError("Order returned for editing: Modify your prompt in the box above and click 'Order Up!' to re-run.");
      promptField.focus();
    });
  }

  /* ---- event listeners ---- */

  submitBtn.addEventListener("click", submitPrompt);

  resetBtn.addEventListener("click", function () {
    promptField.value = "";
    clearAll();
    setLoading(false);
  });

  errorClose.addEventListener("click", hideError);

  btnApprove.addEventListener("click", doApprove);
  btnEdit.addEventListener("click", doEdit);
  btnReject.addEventListener("click", doReject);

  menuBtn.addEventListener("click", function () { menuOverlay.hidden = false; });
  menuClose.addEventListener("click", function () { menuOverlay.hidden = true; });
  menuOverlay.addEventListener("click", function (e) {
    if (e.target === menuOverlay) menuOverlay.hidden = true;
  });

  for (var i = 0; i < testBtns.length; i++) {
    testBtns[i].addEventListener("click", function () {
      promptField.value = this.getAttribute("data-prompt");
      pendingTest = Array.prototype.indexOf.call(testBtns, this);
      submitPrompt();
    });
  }

  soundBtn.addEventListener("click", function () {
    soundOn = !soundOn;
    soundBtn.textContent = soundOn ? "Sound: on" : "Sound: off";
    soundBtn.setAttribute("aria-pressed", soundOn ? "true" : "false");
    if (soundOn) playDing();
  });

  printBtn.addEventListener("click", function () {
    document.body.classList.add("printing-receipt");
    window.print();
    setTimeout(function () { document.body.classList.remove("printing-receipt"); }, 500);
  });

  clearTests.addEventListener("click", function () {
    testedList = [];
    saveTested();
    updateTestUI();
  });

  logo.addEventListener("click", function () {
    logoClicks++;
    clearTimeout(logoTimer);
    logoTimer = setTimeout(function () { logoClicks = 0; }, 2000);
    if (logoClicks >= 5) {
      logoClicks = 0;
      rollPatty();
    }
  });

  loadTested();
  updateTestUI();

  // Enter submits (Shift+Enter for newline)
  promptField.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      submitPrompt();
    }
  });

})();
