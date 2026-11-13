// Activity watcher for pages with the inactive box. The inactivity timeout (data-timeout-minutes on the box, set from
// FrontEndConfig.json) is divided into 30 parts. Every part the Idle class listens for 5 seconds.
// Nothing happened in those 5 seconds: the counter goes up. At 24 (80% of the timeout) the countdown starts and at 30
// isInactive() runs. Activity at any point resets the counter and touches the ActiveUsers row.
var gray = document.getElementById("gray-out");
var inactiveBox = document.getElementById("inactiveBox");
var watcher = null;
var ticker = null;
var countdownTimer = null;
var idleCount = 0;
var timeoutMinutes = inactiveBox ? parseFloat(inactiveBox.dataset.timeoutMinutes) || 15 : 15;
var checkInterval = Math.max(6000, timeoutMinutes * 60000 / 30);   // one of the 30 parts, never shorter than the 5 second listen
var windowIdle = false;     // the Idle class went away during this 5 second window
var windowActive = false;   // the Idle class came back during this 5 second window

if (gray && typeof Idle !== "undefined") {
    watcher = new Idle({
        awayTimeout : 5000,
        onAway : function () { windowIdle = true; },
        onAwayBack : function () { windowActive = true; wokeUp(); }
    });
    ticker = setInterval(checkActivity, checkInterval);
}

function checkActivity()
{
    windowIdle = false;
    windowActive = false;
    watcher.start();

    setTimeout(function () {
        if (windowIdle && !windowActive) {
            idleCount++;
            if (idleCount == 24) showCountdown();
            if (idleCount >= 30) {
                clearInterval(ticker);
                isInactive();
            }
        } else if (!windowActive) {
            wokeUp();
        }
    }, 5200);
}

// Not idle: reset the counter, close the box and tell the server the session is still in use
function wokeUp()
{
    idleCount = 0;
    hideCountdown();
    touchActiveUser();
}

function touchActiveUser()
{
    fetch(window.location.pathname, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({touch: "touch"})
    }).then(function (reply) {
        if (reply.redirected && reply.url.includes("Inactive.php")) window.location = "Inactive.php";   // its row was already removed
    });
}

// Started when the counter reaches 24: shows the box and counts the last 6 parts (20% of the timeout) down to the 30
function showCountdown()
{
    gray.style.display = "block";
    inactiveBox.style.display = "block";
    const deadline = Date.now() + 6 * checkInterval;
    clearInterval(countdownTimer);
    function update()
    {
        const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        document.getElementById("inactiveCountdown").textContent = Math.floor(left / 60) + ":" + String(left % 60).padStart(2, "0") + " minutes";
        if (left === 0) clearInterval(countdownTimer);
    }
    update();
    countdownTimer = setInterval(update, 1000);
}

function hideCountdown()
{
    clearInterval(countdownTimer);
    if (gray) gray.style.display = "none";
    if (inactiveBox) inactiveBox.style.display = "none";
}

// Counter reached 30: log the session out through the current page's own logout handler
function isInactive()
{
    fetch(window.location.pathname, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({logout: "logout", reason: "inactive"})
    }).then(function () {
        window.location = "Inactive.php";
    });
}
