// The admin dashboard: the graph period, the Delete User search and the Refresh button

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

// Refresh button (adminAgent.php dashboard): loads the page again in the background, so every database query runs, and
// swaps the data sections for the new ones. Forms the admin is typing in and the chosen period are left alone.
const refreshBtn = document.getElementById("refresh-btn");
if (refreshBtn) {
    refreshBtn.addEventListener("click", async function () {
        if (refreshBtn.classList.contains("spinning")) return;
        refreshBtn.classList.add("spinning");
        try {
            const reply = await fetch("adminAgent.php", { cache: "no-store" });
            const fresh = new DOMParser().parseFromString(await reply.text(), "text/html");
            if (!fresh.getElementById("period-views")) { location.reload(); return; }   // session ended, the page sends us to login

            for (const id of ["period-views", "all-users-wrap", "active-box", "apps-box", "server-status"]) {
                const oldEl = document.getElementById(id);
                const newEl = fresh.getElementById(id);
                if (!oldEl || !newEl) continue;
                if (id === "server-status") { oldEl.textContent = newEl.textContent.trim(); oldEl.className = newEl.className; }
                else oldEl.innerHTML = newEl.innerHTML;
            }
            changeGraph();                              // re-hides the period views the new HTML brought in
        } catch (problem) { /* keep what is on screen */ }
        refreshBtn.classList.remove("spinning");
    });
}
