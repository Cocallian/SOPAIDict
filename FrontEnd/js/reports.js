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
function throbReport(target)
{
    reportThrobbing = true;
    target.style.fontFamily = "Consolas, 'Courier New', monospace";
    throbber(function (frame) { target.textContent = "Generating the report, this can take a few minutes... " + frame; }, () => reportThrobbing);   // until the report has arrived or failed
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
