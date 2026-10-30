import base64, io, json, os, sys
from pathlib import Path

import matplotlib
matplotlib.use("Agg")                                  # draw into memory, no window
import matplotlib.pyplot as plt
from matplotlib.ticker import MaxNLocator
import numpy as np
import pandas as pd
from jinja2 import Template
from xhtml2pdf import pisa

# reportapi.py has already put what this script needs in place before it runs it:
#   config.json         the periods, the four switches and REPORT_NAME of this request
#   ReportData/*.csv    the full Prompts, Responses, SourcedDocuments and Logs tables, so this script never calls the database
# The finished file goes to Reports/<REPORT_NAME>.pdf, which is what the server is waiting for.
PROJECT = Path(__file__).parent
DATA_FOLDER = PROJECT / "ReportData"
REPORTS_FOLDER = PROJECT / "Reports"

TOP = {"day": 3, "week": 5, "month": 10}               # rows in the busiest and most sourced rankings for each kind of period
TOP_BETWEEN = [(7, 5), (None, 10)]                     # between two dates: 5 rows up to a week long, 10 for anything longer
TOP_SESSIONS = 10                                      # bars in the longest sessions graph
AMBER = "#f5a300"
GREEN = "#2e7d32"


# ---- Reading the request and the tables ----

def read_table(name, date_column):
    path = DATA_FOLDER / f"{name}.csv"
    if not path.exists():
        sys.exit(f"{path.name} is missing from the ReportData folder, the report server writes it before this script runs.")
    table = pd.read_csv(path)
    table[date_column] = pd.to_datetime(table[date_column])
    return table


def periods_from(config):
    # BETWEEN_DAYS, SPECIFIC_WEEK and MONTH hold "start:end" (inclusive days) when that picker was set and "" when it was not.
    # A period starts at the beginning of its first day (00:00:00) and ends at the end of its last day (23:59:59), so the
    # same day twice is that one whole day.
    found = []
    for kind, key in (("day", "BETWEEN_DAYS"), ("week", "SPECIFIC_WEEK"), ("month", "MONTH")):
        text = config.get(key, "").strip()
        if not text:
            continue
        first, last = (pd.Timestamp(part) for part in text.split(":"))
        days = (last - first).days + 1
        if kind == "day" and days > 1:
            kind = "between"                            # two different dates; the same date twice stays a single day
        if kind == "day":
            title = f"Specific date: {first:%a} {first.day} {first:%b %Y}"
        elif kind == "between":
            title = f"Between dates: {first:%a} {first.day} {first:%b %Y} - {last:%a} {last.day} {last:%b %Y}"
        elif kind == "week":
            title = f"Week: {first:%a} {first.day} {first:%b} - {last:%a} {last.day} {last:%b %Y}"
        else:
            title = f"Month: {first:%B %Y}"
        top = TOP[kind] if kind in TOP else next(rows for limit, rows in TOP_BETWEEN if limit is None or days <= limit)
        found.append({"kind": kind, "start": first, "end": last + pd.Timedelta(days=1), "days": days, "top": top, "title": title})   # end is not included
    return found


# ---- Small helpers ----

def png(figure):
    buffer = io.BytesIO()
    figure.savefig(buffer, format="png", bbox_inches="tight")
    plt.close(figure)
    return base64.b64encode(buffer.getvalue()).decode("ascii")


def length(seconds):
    seconds = int(round(seconds))
    hours, rest = divmod(seconds, 3600)
    minutes, seconds = divmod(rest, 60)
    if hours:
        return f"{hours}h {minutes:02d}m"
    return f"{minutes}m {seconds:02d}s" if minutes else f"{seconds}s"


def inside(table, column, period):
    return table[(table[column] >= period["start"]) & (table[column] < period["end"])]


# ---- The four sections ----

def total_responses(responses, period):
    # Line graph: responses (y) over the time period (x). A single date is shown by hour, a week and a month by day.
    # Between two dates it is by day up to about two months, then by week, then by month, so the graph stays readable.
    rows = inside(responses, "CreatedAt", period)
    last_day = period["end"] - pd.Timedelta(days=1)
    if period["kind"] == "day":
        steps = pd.date_range(period["start"], periods=24, freq="h")
        counts = rows["CreatedAt"].dt.floor("h").value_counts()
        labels, x_title = [f"{t:%H}" for t in steps], "Hour of the day"
    elif period["kind"] == "between" and period["days"] > 366:
        steps = pd.date_range(period["start"].replace(day=1), last_day, freq="MS")
        counts = rows["CreatedAt"].dt.to_period("M").dt.to_timestamp().value_counts()
        labels, x_title = [f"{t:%b %y}" for t in steps], "Month"
    elif period["kind"] == "between" and period["days"] > 62:
        monday = period["start"] - pd.Timedelta(days=period["start"].weekday())
        steps = pd.date_range(monday, last_day, freq="7D")
        counts = (rows["CreatedAt"].dt.floor("D") - pd.to_timedelta(rows["CreatedAt"].dt.weekday, unit="D")).value_counts()
        labels, x_title = [f"{t.day} {t:%b}" for t in steps], "Week starting"
    else:
        steps = pd.date_range(period["start"], last_day, freq="D")
        counts = rows["CreatedAt"].dt.floor("D").value_counts()
        if period["kind"] == "week":
            labels, x_title = [f"{t:%a} {t.day}" for t in steps], "Day of the week"
        elif period["kind"] == "between":
            labels, x_title = [f"{t.day} {t:%b}" for t in steps], "Day"
        else:
            labels, x_title = [str(t.day) for t in steps], "Day of the month"
    values = counts.reindex(steps, fill_value=0).to_numpy()

    step = 1 if len(values) <= 31 else -(-len(values) // 16)            # show about 16 labels when there are many points
    figure, axes = plt.subplots(figsize=(7.5, 3.0), dpi=150)
    axes.plot(np.arange(len(values)), values, color=AMBER, marker="o", linewidth=2, markersize=4 if step > 1 else 6)
    axes.set_xticks(np.arange(0, len(values), step))
    axes.set_xticklabels(labels[::step], fontsize=7)
    axes.set_xlabel(x_title)
    axes.set_ylabel("Responses")
    axes.set_ylim(bottom=0)
    axes.yaxis.set_major_locator(MaxNLocator(integer=True))
    axes.grid(axis="y", alpha=0.3)
    return {"total": int(values.sum()), "image": png(figure)}


def ranking(rows, column, period, label_of):
    # The top rows of a count, most first; equal counts keep the earlier one first
    counts = rows[column].value_counts().rename_axis("what").reset_index(name="total")
    counts = counts.sort_values(["total", "what"], ascending=[False, True]).head(period["top"])
    return [{"rank": i, "what": label_of(row.what), "count": int(row.total)} for i, row in enumerate(counts.itertuples(), start=1)]


def busiest(responses, period):
    rows = inside(responses, "CreatedAt", period)
    n = period["top"]
    if period["kind"] == "day":                         # one date has only one day, so its busiest hours are ranked
        rows = rows.assign(slot=rows["CreatedAt"].dt.floor("h"))
        label = lambda t: f"{t:%H}:00 - {t + pd.Timedelta(hours=1):%H}:00"
        return {"heading": f"Busiest hours (top {n})", "column": "Hour", "rows": ranking(rows, "slot", period, label)}
    rows = rows.assign(slot=rows["CreatedAt"].dt.floor("D"))
    label = lambda t: f"{t:%a} {t.day} {t:%b %Y}"
    return {"heading": f"Busiest days (top {n})", "column": "Day", "rows": ranking(rows, "slot", period, label)}


def most_sourced(sourced, period):
    rows = inside(sourced, "SourcedAt", period)
    n = period["top"]
    return {"heading": f"Most sourced documents (top {n})", "rows": ranking(rows, "Title", period, str)}


def all_sessions(logs):
    # A session runs from a login to the next logout or inactive line of the same username
    found, still_open = [], 0
    for username, lines in logs.sort_values("LoggedAt").groupby("Username"):
        started = None
        for line in lines.itertuples():
            if line.Event == "login":
                started = line.LoggedAt
            elif started is not None:
                found.append({"user": username, "start": started, "seconds": (line.LoggedAt - started).total_seconds(), "ended_by": line.Event})
                started = None
        still_open += started is not None
    table = pd.DataFrame(found, columns=["user", "start", "seconds", "ended_by"])
    table["start"] = pd.to_datetime(table["start"])
    return table, still_open


def longest_sessions(sessions, still_open, period):
    # Horizontal bar graph, longest session at the top
    rows = inside(sessions, "start", period).sort_values("seconds", ascending=False).head(TOP_SESSIONS)
    result = {"count": len(rows), "still_open": still_open, "image": None}
    if rows.empty:
        return result

    labels = [f"{r.user} - {r.start:%d %b %H:%M}" for r in rows.itertuples()]
    minutes = rows["seconds"].to_numpy() / 60
    figure, axes = plt.subplots(figsize=(7.5, max(1.6, 0.3 * len(rows) + 0.9)), dpi=150)
    axes.barh(np.arange(len(rows)), minutes, color=GREEN)
    axes.set_yticks(np.arange(len(rows)))
    axes.set_yticklabels(labels, fontsize=7)
    axes.invert_yaxis()                                 # first row is drawn at the bottom by default, the longest must be on top
    axes.set_xlabel("Session length (minutes)")
    axes.set_xlim(right=minutes.max() * 1.25)
    for i, seconds in enumerate(rows["seconds"]):
        axes.text(minutes[i], i, "  " + length(seconds), va="center", fontsize=7)
    axes.grid(axis="x", alpha=0.3)
    result["image"] = png(figure)
    return result


# ---- The document ----

PAGE = Template("""
<html>
<head>
<meta charset="utf-8">
<style>
    @page {
        size: a4 portrait;
        margin: 1.8cm 1.8cm 2.2cm 1.8cm;
        @frame footer { -pdf-frame-content: footer; bottom: 0.8cm; margin-left: 1.8cm; margin-right: 1.8cm; height: 0.8cm; }
    }
    body { font-family: Helvetica; font-size: 10pt; color: #222222; }
    h1 { font-size: 20pt; margin: 0 0 4pt 0; }
    h2 { font-size: 15pt; margin: 0 0 8pt 0; padding-bottom: 3pt; border-bottom: 2pt solid #f5a300; }
    h3 { font-size: 11.5pt; margin: 14pt 0 5pt 0; -pdf-keep-with-next: true; }
    .meta { color: #666666; font-size: 9pt; }
    .note { color: #666666; font-size: 9pt; }
    table.rank { width: 100%; }
    table.rank th { background-color: #eeeeee; text-align: left; padding: 4pt; border-bottom: 1pt solid #cccccc; }
    table.rank td { padding: 4pt; border-bottom: 1pt solid #eeeeee; }
    .num { text-align: right; }
    .gap { page-break-before: always; }
</style>
</head>
<body>
<h1>SOP Agent report</h1>
<p class="meta">{{ name }}<br/>Created {{ created }}<br/>Included: {{ included }}</p>

{% for period in periods %}
<div{% if not loop.first %} class="gap"{% endif %}>
<h2>{{ period.title }}</h2>

{% if period.total_responses %}
<h3>Total responses: {{ period.total_responses.total }}</h3>
<img src="data:image/png;base64,{{ period.total_responses.image }}" width="500"/>
{% endif %}

{% if period.busiest %}
<h3>{{ period.busiest.heading }}</h3>
{% if period.busiest.rows %}
<table class="rank">
<tr><th>Rank</th><th>{{ period.busiest.column }}</th><th class="num">Responses</th></tr>
{% for row in period.busiest.rows %}<tr><td>{{ row.rank }}</td><td>{{ row.what }}</td><td class="num">{{ row.count }}</td></tr>{% endfor %}
</table>
{% else %}<p class="note">No responses in this period.</p>{% endif %}
{% endif %}

{% if period.most_sourced %}
<h3>{{ period.most_sourced.heading }}</h3>
{% if period.most_sourced.rows %}
<table class="rank">
<tr><th>Rank</th><th>Document</th><th class="num">Times sourced</th></tr>
{% for row in period.most_sourced.rows %}<tr><td>{{ row.rank }}</td><td>{{ row.what }}</td><td class="num">{{ row.count }}</td></tr>{% endfor %}
</table>
{% else %}<p class="note">No documents were sourced in this period.</p>{% endif %}
{% endif %}

{% if period.longest_sessions %}
<h3>Longest sessions</h3>
{% if period.longest_sessions.image %}
<img src="data:image/png;base64,{{ period.longest_sessions.image }}" width="500"/>
{% else %}<p class="note">No finished sessions started in this period.</p>{% endif %}
{% if period.longest_sessions.still_open %}<p class="note">{{ period.longest_sessions.still_open }} session(s) have not ended yet and are not counted.</p>{% endif %}
{% endif %}
</div>
{% endfor %}

<div id="footer" class="meta">{{ name }} - page <pdf:pagenumber/> of <pdf:pagecount/></div>
</body>
</html>
""", autoescape=True)                                   # names and document titles can contain & or <


def clear_data():
    # The tables hold every question and answer, so they are not left lying around once the report has been made
    for path in DATA_FOLDER.glob("*"):
        if path.is_file():
            try:
                path.unlink()
            except OSError:
                pass                                    # a file that can't be deleted right now must not spoil the report


def make_report():
    config = json.loads((PROJECT / "config.json").read_text(encoding="utf-8"))
    name = config.get("REPORT_NAME", "").strip()
    if not name:
        sys.exit("REPORT_NAME in config.json is empty.")

    wanted = {key: config.get(key, "False") == "True"
              for key in ("TOTAL_RESPONSES", "BUSIEST_DAYS", "MOST_SOURCED", "LONGEST_SESSIONS")}
    if not any(wanted.values()):
        sys.exit("None of TOTAL_RESPONSES, BUSIEST_DAYS, MOST_SOURCED or LONGEST_SESSIONS is True in config.json.")
    periods = periods_from(config)
    if not periods:
        sys.exit("None of BETWEEN_DAYS, SPECIFIC_WEEK or MONTH is set in config.json.")

    # The tables are read once and every period and section below works on these
    responses = read_table("Responses", "CreatedAt")
    sourced = read_table("SourcedDocuments", "SourcedAt")
    sessions, still_open = all_sessions(read_table("Logs", "LoggedAt"))

    for period in periods:
        period["total_responses"] = total_responses(responses, period) if wanted["TOTAL_RESPONSES"] else None
        period["busiest"] = busiest(responses, period) if wanted["BUSIEST_DAYS"] else None
        period["most_sourced"] = most_sourced(sourced, period) if wanted["MOST_SOURCED"] else None
        period["longest_sessions"] = longest_sessions(sessions, still_open, period) if wanted["LONGEST_SESSIONS"] else None

    names = {"TOTAL_RESPONSES": "total responses", "BUSIEST_DAYS": "busiest days",
             "MOST_SOURCED": "most sourced", "LONGEST_SESSIONS": "longest sessions"}
    html = PAGE.render(name=name, created=f"{pd.Timestamp.now():%d %b %Y %H:%M}", periods=periods,
                       included=", ".join(text for key, text in names.items() if wanted[key]))

    REPORTS_FOLDER.mkdir(exist_ok=True)
    part = REPORTS_FOLDER / f"{name}.pdf.part"          # built under another name, so the server never sees half a file
    with open(part, "wb") as file:
        result = pisa.CreatePDF(html, dest=file)
    if result.err:
        part.unlink(missing_ok=True)
        sys.exit("xhtml2pdf could not build the PDF.")
    os.replace(part, REPORTS_FOLDER / f"{name}.pdf")


def main():
    try:
        make_report()
    finally:
        clear_data()                                    # also when the report failed, the copies are never needed again


if __name__ == "__main__":
    main()
