// Server card (adminAgent.php): Start server shows the loading popup, in the same place as the error popup, with the
// throbber until the server answers
const startServerForm = document.getElementById("start-server-form");
if (startServerForm) {
    const serverPopup = document.getElementById("loadingPopup");
    const serverText = document.getElementById("loadingText");
    const SERVER_WAIT = 5 * 60 * 1000;                  // give up after 5 minutes
    let waitingForServer = false;
    let hideTimer = null;

    async function askPage(fields) {
        const reply = await fetch("adminAgent.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(fields)
        });
        return (await reply.text()).trim();
    }

    // The green / red "Server running" text in the card: asks the page whether anything answers on the server's port
    const statusText = document.getElementById("server-status");
    async function refreshServerStatus() {
        let up = false;
        try { up = (await askPage({ "server-status": "1" })) === "up"; } catch (problem) { /* shows as not running */ }
        statusText.textContent = up ? "Server running" : "Server not running";
        statusText.className = up ? "up" : "down";
        return up;
    }
    setInterval(refreshServerStatus, 5000);

    // Replaces the throbber with a result that goes away by itself after 7 seconds
    function showServerResult(text, kind) {
        waitingForServer = false;                       // this is what stops the throbber
        serverText.textContent = text;
        serverPopup.className = kind;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () { serverPopup.classList.add("hidden"); }, 7000);
    }

    function throbServer() {
        throbber(function (frame) { serverText.textContent = "Starting the server, loading the models... " + frame; }, () => waitingForServer);
    }

    document.getElementById("closeLoading").addEventListener("click", function () {
        waitingForServer = false;                       // also stops the waiting below
        serverPopup.classList.add("hidden");
    });

    startServerForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        if (waitingForServer) { return; }

        let answer;
        try {
            answer = await askPage({ "start-server": "1" });   // the first time this opens the file window to choose LocalAIServ.py
        } catch (problem) {
            answer = "error: Couldn't reach the page, try again";
        }
        if (answer === "running") { showServerResult("The server is already running", "done"); return; }
        if (answer !== "started") { showServerResult(answer.replace(/^error:\s*/, "") || "The server couldn't be started", "failed"); return; }

        waitingForServer = true;
        clearTimeout(hideTimer);
        serverPopup.className = "";
        throbServer();

        const giveUpAt = Date.now() + SERVER_WAIT;
        let up = false;
        while (waitingForServer && !up && Date.now() < giveUpAt) {
            await sleep(2000);
            try { up = (await askPage({ "server-status": "1" })) === "up"; } catch (problem) { /* try again in 2 seconds */ }
        }

        refreshServerStatus();                          // the text in the card follows straight away
        if (!waitingForServer) { return; }              // the popup was closed
        if (up) { showServerResult("The server is running", "done"); }
        else { showServerResult("The server didn't start in 5 minutes, check its window", "failed"); }
    });
}
