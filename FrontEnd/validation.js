// A username is valid when it is "something" followed by one of the domains listed under [org_name] in passwords.txt
function validateUsername(name, domains)
{
    name = name.trim().toLowerCase();
    for (const domain of domains) {
        const d = domain.toLowerCase();
        if (name.endsWith(d) && /^[^\s@]+$/.test(name.slice(0, name.length - d.length))) return true;
    }
    return false;
}

// The allowed domains travel on the username input as data-domains (filled in by PHP from every line under [org_name])
// Checked as you type, and again on submit
function checkUsername()
{
    const input = document.getElementById("new-username");
    const domains = JSON.parse(input.dataset.domains);
    input.setCustomValidity(validateUsername(input.value, domains) ? "" : "The username must be an email ending in: " + domains.join(", "));
}

if (document.getElementById("new-username")) {
    document.getElementById("new-username").addEventListener("input", checkUsername);
    document.getElementById("new-user-form").addEventListener("submit", function (e) {
        checkUsername();
        if (!document.getElementById("new-username").checkValidity()) {
            e.preventDefault();
            document.getElementById("new-username").reportValidity();
        }
    });
    checkUsername();
}

// One rule per <li> in #password-rules, in the same order
const passwordRules = [
    /.{8,}/,
    /[0-9]/,
    /[a-z]/,
    /[A-Z]/,
    /[^A-Za-z0-9]/
];

// Ticks off the checklist for the typed password, returns true when every rule passes
function ValidatePassword(password)
{
    const items = document.querySelectorAll("#password-rules li");
    let valid = true;
    passwordRules.forEach(function (rule, i) {
        const ok = rule.test(password);
        items[i].classList.toggle("checked", ok);
        if (!ok) valid = false;
    });
    return valid;
}

if (document.getElementById("new-password")) {
    document.getElementById("new-password").addEventListener("input", function () {
        const pass = document.getElementById("new-password");
        const confirm = document.getElementById("confirm-password");
        pass.setCustomValidity(ValidatePassword(pass.value) ? "" : "The password doesn't meet every rule in the list");
        confirm.setCustomValidity(confirm.value === pass.value ? "" : "The passwords don't match");
    });
    document.getElementById("confirm-password").addEventListener("input", function () {
        const pass = document.getElementById("new-password");
        const confirm = document.getElementById("confirm-password");
        confirm.setCustomValidity(confirm.value === pass.value ? "" : "The passwords don't match");
    });
}

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

function CArr(arr, num)
{
    const leng = arr.length;
    var safe = ((num % leng) + leng) % leng;
    return arr[safe];
}

function startSearch(e)
{
    e.preventDefault();
    if(!document.getElementById("user-input").value.trim()) return;
    throb();
    setTimeout(() => e.target.submit(), 100);
}

async function throb()
{
    const ta = document.getElementById("ta");
    ta.style.fontFamily = "Consolas, 'Courier New', monospace";
    const ani1 = ["▖", "▘","▝" ,"▗", "▖", "▘","▝" ,"▗"];
    const ani2 = ["▞", "▚", "▞", "▚", "▞", "▚", "▞", "▚"];
    const ani3 = ["▙", "▛", "▜", "▟", "▙", "▛", "▜", "▟"];
    const ani4 = ["◣", "◤", "◥", "◢", "◣", "◤", "◥", "◢"];
    const ani5 = ["◱", "◰", "◳", "◲", "◱", "◰", "◳", "◲"];
    const ani6 = ["◒", "◐", "◓", "◑", "◒", "◐", "◓", "◑"];
    const ani7 = ["__", "|", "▔", " |", "__", "|", "▔", " |"];
    const ani8 = ["◟", "◜", "◠", "◝", "◞", "◡", "◟", "◜", "◠", "◝", "◞", "◡"];
    const sizes = [ani1, ani2, ani3, ani4, ani5, ani6, ani7, ani8];
    const min = 0;
    const max = sizes.length - 1;
    while(true)
    {
        var rng = Math.floor(Math.random() * (max - min + 1)) + min;
        const sel = rng;
        for(var i = 0; i < sizes[sel].length * 2; i++)
        {
            ta.value = "\n Searching database and generating a response..." + CArr(sizes[sel], i);
            await sleep(150);
        }
    }
}

function togglePanel(open)
{
    document.getElementById("panel").classList.toggle("open", open);
    document.getElementById("close-btn").classList.toggle("show", open);
}

// shows or hides the Prompt history table
function toggleHistory()
{
    document.getElementById("history").classList.toggle("show");
}

// Shows the chosen period's stats and graph and hides the others, no page reload
function changeGraph()
{
    const periodSelect = document.getElementById("period-select");
    for (const view of document.querySelectorAll(".period-view")) {
        view.hidden = view.dataset.period !== periodSelect.value;
    }
    try { localStorage.setItem("period", periodSelect.value); } catch (e) {}
}

// Everything below needs the page's elements, so it runs once the page has loaded (the script is deferred)
const periodSelect = document.getElementById("period-select");
if (periodSelect) {
    try {
        const saved = localStorage.getItem("period");
        if (saved && [...periodSelect.options].some(o => o.value === saved)) periodSelect.value = saved;
    } catch (e) {}
    periodSelect.addEventListener("change", changeGraph);
    changeGraph();
}

const search = document.getElementById("user-search");
const results = document.getElementById("user-results");
let searchTimer;
if (search) {
    search.addEventListener("input", function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async function () {
            const body = results.tBodies[0];
            body.replaceChildren();
            if (search.value.trim() === "") { results.hidden = true; return; }
            const reply = await fetch("adminAgent.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: new URLSearchParams({"search-user": search.value})
            });
            const people = await reply.json();
            if (people.length === 0) people.push(null);
            for (const person of people) {
                const row = body.insertRow();
                const cell = row.insertCell();
                if (person === null) { cell.textContent = "No users found"; row.style.cursor = "default"; continue; }
                cell.textContent = person.name;
                row.insertCell().textContent = person.role;
                row.addEventListener("click", function () {
                    if (!confirm("Delete " + person.role + " " + person.name + "?")) return;
                    document.getElementById("dname").value = person.name;
                    document.getElementById("drole").value = person.role;
                    document.getElementById("del-user").submit();
                });
            }
            results.hidden = false;
        }, 250);
    });
}

// Login page: block submission unless both fields have a non-blank value
const loginForm = document.getElementById("loginForm");
if (loginForm) {
    const uname = document.getElementById("uname");
    const pass = document.getElementById("pass");
    const fieldError = document.getElementById("fieldError");

    loginForm.addEventListener("submit", function (e) {
        let valid = true;

        [uname, pass].forEach(function (input) {
            if (input.value == null || input.value.trim() === "") {
                input.classList.add("invalid");
                valid = false;
            } else {
                input.classList.remove("invalid");
            }
        });

        if (!valid) {
            e.preventDefault();
            fieldError.textContent = "Please enter your username and password.";
        } else {
            fieldError.textContent = "";
        }
    });

    // Clear the error for a field as soon as the user types in it
    [uname, pass].forEach(function (input) {
        input.addEventListener("input", function () {
            if (input.value.trim() !== "") {
                input.classList.remove("invalid");
            }
        });
    });
}

// Close the popups on click, or automatically after 5 seconds
for (const [id, closeId] of [["errPopup", "closePopup"], ["successPopup", "closeSuccess"]]) {
    const popup = document.getElementById(id);
    if (popup) {
        document.getElementById(closeId).addEventListener("click", function () {
            popup.classList.add("hidden");
        });
        setTimeout(function () {
            popup.classList.add("hidden");
        }, 7000);
    }
}

// Activity watcher for pages with the inactive box. Every 30 seconds the Idle class listens for 5 seconds.
// Nothing happened in those 5 seconds: the counter goes up. At 24 (12 minutes) the countdown starts and at 30
// (15 minutes) isInactive() runs. Activity at any point resets the counter and touches the ActiveUsers row.
var gray = document.getElementById("gray-out");
var inactiveBox = document.getElementById("inactiveBox");
var watcher = null;
var ticker = null;
var countdownTimer = null;
var idleCount = 0;
var windowIdle = false;     // the Idle class went away during this 5 second window
var windowActive = false;   // the Idle class came back during this 5 second window

if (gray && typeof Idle !== "undefined") {
    watcher = new Idle({
        awayTimeout : 5000,
        onAway : function () { windowIdle = true; },
        onAwayBack : function () { windowActive = true; wokeUp(); }
    });
    ticker = setInterval(checkActivity, 30000);
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

// Started when the counter reaches 24: shows the box and counts the 3 minutes down to the 30
function showCountdown()
{
    gray.style.display = "block";
    inactiveBox.style.display = "block";
    const deadline = Date.now() + 180000;
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

// Reports page: checks every part of the form. The limits come from reports.php: nothing before the oldest entry in the
// database (data-earliest) and nothing after today (data-today). Each problem is shown under the part it belongs to.
let reportAttempted = false;                            // the "pick something" messages wait until Generate has been pressed once

function niceDate(text)
{
    const d = new Date(text + "T00:00:00");
    return d.getDate() + " " + d.toLocaleString("en-GB", {month: "short"}) + " " + d.getFullYear();
}

function isoLocal(d)
{
    return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function reportProblems(checkAll)
{
    const form = document.getElementById("report-form");
    const earliest = form.dataset.earliest, today = form.dataset.today;
    const from = document.getElementById("report-from").value, to = document.getElementById("report-to").value;
    const week = document.getElementById("report-week").value;
    const month = document.getElementById("report-month").value, year = document.getElementById("report-year").value;
    const problems = {period: "", date: "", week: "", month: "", sections: ""};

    // Between two dates: one date on its own is that one day
    if (from !== "" || to !== "") {
        const first = from || to, last = to || from;
        if (first > today || last > today) problems.date = "That date hasn't happened yet, the latest you can pick is " + niceDate(today) + ".";
        else if (first < earliest) problems.date = "The database has nothing before " + niceDate(earliest) + ", pick that day or a later one.";
        else if (last < first) problems.date = "The end date comes before the start date.";
    }

    // Week: it has to have started and not have ended before the oldest entry
    if (week !== "") {
        const end = new Date(week + "T00:00:00");
        end.setDate(end.getDate() + 6);
        if (week > today) problems.week = "That week hasn't started yet.";
        else if (isoLocal(end) < earliest) problems.week = "That week ends before the oldest entry in the database (" + niceDate(earliest) + ").";
    }

    // Month: it needs a year, has to have started and not have ended before the oldest entry
    if (month !== "") {
        const select = document.getElementById("report-month");
        const name = select.options[select.selectedIndex].text + " " + year;
        const m = parseInt(month, 10), y = parseInt(year, 10);
        if (!y) {
            problems.month = "Pick a year for the month.";
        } else {
            const first = y + "-" + String(m).padStart(2, "0") + "-01";
            const last = y + "-" + String(m).padStart(2, "0") + "-" + String(new Date(y, m, 0).getDate()).padStart(2, "0");
            if (first > today) problems.month = name + " hasn't started yet.";
            else if (last < earliest) problems.month = name + " is before the oldest entry in the database (" + niceDate(earliest) + ").";
        }
    }

    if (checkAll && from === "" && to === "" && week === "" && month === "") {
        problems.period = "Set at least one of the dates, the week or the month.";
    }
    if (checkAll && !document.querySelector('input[name="sections[]"]:checked')) {
        problems.sections = "Switch on at least one thing to include in the report.";
    }
    return problems;
}

// Puts each message under its own part of the form and returns true when there is nothing wrong
function showReportProblems(problems)
{
    for (const name in problems) {
        const line = document.querySelector('[data-error="' + name + '"]');
        line.textContent = problems[name];
        line.hidden = problems[name] === "";
    }
    for (const name of ["date", "week", "month"]) {
        document.querySelector('[data-block="' + name + '"]').classList.toggle("invalid", problems[name] !== "");
    }
    return Object.values(problems).every(message => message === "");
}

// Checks again as the form changes, so a mistake shows up straight away and goes away when it is fixed
function checkReportForm()
{
    return showReportProblems(reportProblems(reportAttempted));
}

// Reports page: the date range, week and month pickers each start unset ("-" or empty). One that has been set is active.
if (document.getElementById("report-from")) {
    const from = document.getElementById("report-from");
    const to = document.getElementById("report-to");
    const blocks = {
        date:  { box: document.querySelector('[data-block="date"]'),  isSet: () => from.value !== "" || to.value !== "" },
        week:  { box: document.querySelector('[data-block="week"]'),  isSet: () => document.getElementById("report-week").value !== "" },
        month: { box: document.querySelector('[data-block="month"]'), isSet: () => document.getElementById("report-month").value !== "" }
    };

    function updateActive()
    {
        for (const name in blocks) {
            blocks[name].box.classList.toggle("active", blocks[name].isSet());
        }
        // the year only counts together with a month
        document.getElementById("report-year").disabled = document.getElementById("report-month").value === "";
    }

    // The last day can't come before the first day, and one date on its own means that one whole day
    function keepRangeInOrder()
    {
        to.min = from.value || document.getElementById("report-form").dataset.earliest;
        if (from.value !== "" && to.value !== "" && to.value < from.value) to.value = from.value;
    }

    for (const id of ["report-from", "report-to", "report-week", "report-month", "report-year"]) {
        document.getElementById(id).addEventListener("input", function () { updateActive(); checkReportForm(); });
        document.getElementById(id).addEventListener("change", function () { updateActive(); checkReportForm(); });
    }
    for (const toggle of document.querySelectorAll('input[name="sections[]"]')) {
        toggle.addEventListener("change", checkReportForm);
    }
    from.addEventListener("change", function () { keepRangeInOrder(); updateActive(); checkReportForm(); });
    to.addEventListener("change", function () { keepRangeInOrder(); updateActive(); checkReportForm(); });
    document.getElementById("today-btn").addEventListener("click", function () {
        from.value = to.value = document.getElementById("today-btn").dataset.today;
        keepRangeInOrder();
        updateActive();
        checkReportForm();
    });
    document.getElementById("clear-date-btn").addEventListener("click", function () {
        from.value = to.value = "";
        keepRangeInOrder();
        updateActive();
        checkReportForm();
    });
    updateActive();
}

// Reports page: the throbber from throb() above, shown under the form while the report is made
let reportThrobbing = false;
async function throbReport(target)
{
    reportThrobbing = true;
    target.style.fontFamily = "Consolas, 'Courier New', monospace";
    const sizes = [
        ["▖", "▘", "▝", "▗", "▖", "▘", "▝", "▗"],
        ["▞", "▚", "▞", "▚", "▞", "▚", "▞", "▚"],
        ["▙", "▛", "▜", "▟", "▙", "▛", "▜", "▟"],
        ["◣", "◤", "◥", "◢", "◣", "◤", "◥", "◢"],
        ["◱", "◰", "◳", "◲", "◱", "◰", "◳", "◲"],
        ["◒", "◐", "◓", "◑", "◒", "◐", "◓", "◑"],
        ["__", "|", "▔", " |", "__", "|", "▔", " |"],
        ["◟", "◜", "◠", "◝", "◞", "◡", "◟", "◜", "◠", "◝", "◞", "◡"]
    ];
    while (reportThrobbing)                             // runs until the report has arrived or failed
    {
        const sel = Math.floor(Math.random() * sizes.length);
        for (let i = 0; i < sizes[sel].length * 2 && reportThrobbing; i++)
        {
            target.textContent = "Generating the report, this can take a few minutes... " + CArr(sizes[sel], i);
            await sleep(150);
        }
    }
}

// Sends the form with fetch instead of a normal submit, so the page stays and knows when the file has arrived
if (document.getElementById("report-form")) {
    document.getElementById("report-form").addEventListener("submit", async function (e) {
        e.preventDefault();
        reportAttempted = true;
        const problems = reportProblems(true);
        if (!showReportProblems(problems)) {            // nothing is sent until every part of the form is right
            const first = Object.keys(problems).find(name => problems[name] !== "");
            document.querySelector('[data-error="' + first + '"]').scrollIntoView({block: "center", behavior: "smooth"});
            return;
        }
        const button = e.target.querySelector(".generate-btn");
        const status = document.getElementById("report-status");
        const text = document.getElementById("report-status-text");
        const data = new FormData(e.target);
        data.append("generate", "generate");

        button.disabled = true;
        status.className = "report-status";
        status.hidden = false;
        throbReport(text);

        try {
            const reply = await fetch("reports.php", {method: "POST", body: data, headers: {"X-Requested-With": "fetch"}});
            if (!reply.ok) throw new Error((await reply.text()).trim() || "The report could not be made.");

            const name = ((reply.headers.get("Content-Disposition") || "").match(/filename="?([^";]+)"?/) || [])[1] || "report.pdf";
            const url = URL.createObjectURL(await reply.blob());
            const link = document.createElement("a");   // the browser saves it (or asks where to, depending on its download setting)
            link.href = url;
            link.download = name;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 10000);

            reportThrobbing = false;
            text.style.fontFamily = "";
            text.textContent = "Report ready: " + name + " has been sent to you.";
            status.classList.add("done");
        } catch (error) {
            reportThrobbing = false;
            text.style.fontFamily = "";
            text.textContent = error.message;
            status.classList.add("failed");
        } finally {
            button.disabled = false;
        }
    });
}
