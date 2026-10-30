import configparser, csv, json, os, subprocess, sys, tempfile, threading, time
from datetime import date, datetime, timedelta
from pathlib import Path

from flask import request, send_file

# The four switches on reports.php: their name in the request, the key GenerateReport.py reads in config.json,
# and the letter they add to REPORT_NAME. The order here is the order of the letters (Sourced, Busiest, Responses, Longest).
SECTIONS = {
    "sourced":  ("MOST_SOURCED",     "S"),
    "busiest":  ("BUSIEST_DAYS",     "B"),
    "total":    ("TOTAL_RESPONSES",  "R"),
    "sessions": ("LONGEST_SESSIONS", "L"),
}
MAX_WAIT = 300                                          # seconds to wait for the report file to show up
TABLES = ("Prompts", "Responses", "SourcedDocuments", "Logs")   # the full tables GenerateReport.py works through


def parse_day(text):
    return datetime.strptime(text, "%Y-%m-%d").date()


class RequestError(ValueError):
    pass                                                 # a message that is fine to show the admin


def period_ranges(form):
    # The date range, week and month pickers on reports.php: each one that was set becomes "start:end" (inclusive days,
    # the start is the beginning of the first day and the end is the end of the last day), the others stay ""
    periods = {"date": "", "week": "", "month": ""}

    first, last = form.get("date_from", "").strip(), form.get("date_to", "").strip()
    if first or last:
        a, b = parse_day(first or last), parse_day(last or first)     # one date on its own is that one whole day
        if b < a:
            raise RequestError("The end date comes before the start date.")
        periods["date"] = f"{a}:{b}"

    week = form.get("week", "").strip()
    if week:
        start = parse_day(week)
        start -= timedelta(days=start.weekday())       # any day in the week works, the week starts on the Monday
        periods["week"] = f"{start}:{start + timedelta(days=6)}"

    month = form.get("month", "").strip()
    if month:
        m, y = int(month), int(form.get("year", "").strip())
        if not 1 <= m <= 12 or not 2000 <= y <= 2100:
            raise ValueError("month or year out of range")
        end = date(y + 1, 1, 1) if m == 12 else date(y, m + 1, 1)
        periods["month"] = f"{date(y, m, 1)}:{end - timedelta(days=1)}"

    return periods


def write_config(config_path, changes):
    # Changes only the keys given and keeps the rest of config.json as it was
    with open(config_path, encoding="utf-8") as f:
        config = json.load(f)
    config.update(changes)
    temporary = config_path.with_suffix(".json.tmp")
    with open(temporary, "w", encoding="utf-8") as f:
        json.dump(config, f, indent=4)
    os.replace(temporary, config_path)                 # swap in one step, so nothing reads a half-written config.json


def connect(passwords_file):
    import pymysql
    secrets = configparser.ConfigParser(strict=False)  # the same MySQL login the website uses
    secrets.read(passwords_file, encoding="utf-8")
    db = {key: value.strip('"') for key, value in secrets["database"].items()}
    return pymysql.connect(host=db["db_host"], user=db["db_user"], password=db["db_pass"], database="SOPAgent", connect_timeout=10)


def earliest_entry(passwords_file):
    # The date of the oldest thing in the tables a report is built from, None while they are all empty
    connection = connect(passwords_file)
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT MIN(t) FROM (SELECT MIN(CreatedAt) AS t FROM Prompts UNION ALL SELECT MIN(CreatedAt) FROM Responses "
                           "UNION ALL SELECT MIN(SourcedAt) FROM SourcedDocuments UNION ALL SELECT MIN(LoggedAt) FROM Logs) AS oldest")
            found = cursor.fetchone()[0]
        return found.date() if found else None
    finally:
        connection.close()


def nice(day):
    return f"{day.day} {day:%b %Y}"


def check_available(periods, earliest, today):
    # A period has to be able to hold data: nothing after today (it hasn't happened yet) and nothing that ends before the
    # oldest entry. A week or month that only partly overlaps is fine, so this week and this month can be picked.
    if earliest is None:
        raise RequestError("There is no data in the database yet, so there is nothing to report on.")
    for name, text in periods.items():
        if not text:
            continue
        first, last = (parse_day(part) for part in text.split(":"))
        label = {"date": "date", "week": "week", "month": "month"}[name]
        if first > today:
            raise RequestError(f"That {label} hasn't started yet, today is {nice(today)}.")
        if name == "date" and last > today:
            raise RequestError(f"The end date {nice(last)} hasn't happened yet, today is {nice(today)}.")
        if name == "date" and first < earliest:
            raise RequestError(f"The start date {nice(first)} is before the oldest entry in the database ({nice(earliest)}).")
        if last < earliest:
            raise RequestError(f"The {label} ends before the oldest entry in the database ({nice(earliest)}), so it has no data.")


def export_tables(folder, passwords_file):
    # One query per table, the whole table, written to <folder>/<Table>.csv. GenerateReport.py reads these and never calls the database.
    folder.mkdir(exist_ok=True)
    connection = connect(passwords_file)
    try:
        with connection.cursor() as cursor:
            for table in TABLES:
                cursor.execute(f"SELECT * FROM {table}")        # the names come from TABLES above, never from the request
                temporary = folder / f"{table}.csv.tmp"
                with open(temporary, "w", newline="", encoding="utf-8") as f:
                    writer = csv.writer(f)
                    writer.writerow([column[0] for column in cursor.description])
                    for row in cursor:
                        writer.writerow(["" if value is None else value for value in row])
                os.replace(temporary, folder / f"{table}.csv")
    finally:
        connection.close()


def register_report_routes(app, project, script=None, max_wait=MAX_WAIT, passwords_file=None):
    script = Path(script) if script else project / "GenerateReport.py"
    passwords_file = Path(passwords_file) if passwords_file else project / "FrontEnd" / "passwords.txt"
    data_folder = project / "ReportData"
    config_path = project / "config.json"
    reports_folder = project / "Reports"
    reports_folder.mkdir(exist_ok=True)
    python = "python" if getattr(sys, "frozen", False) else sys.executable   # a built .exe can't run a .py file
    one_report_at_a_time = threading.Lock()             # the request goes into config.json, so two can't share it

    def find_file(name):
        for path in reports_folder.iterdir():
            if path.is_file() and path.stem == name:
                return path
        return None

    @app.post("/report")
    def report():
        # reports.php posts: date_from + date_to, week, month + year (each empty when not set) and sections (comma separated)
        try:
            periods = period_ranges(request.form)
        except RequestError as error:
            return str(error), 400
        except ValueError:
            return "One of the dates is not valid.", 400
        if not any(periods.values()):
            return "Set at least one of the dates, the week or the month.", 400

        chosen = []
        for value in request.form.getlist("sections"):
            chosen += [s.strip() for s in value.split(",") if s.strip()]
        if not chosen or any(s not in SECTIONS for s in chosen):
            return "Choose at least one of: " + ", ".join(SECTIONS) + ".", 400

        try:                                            # dates that don't exist yet, or that are older than anything in the database
            check_available(periods, earliest_entry(passwords_file), date.today())
        except RequestError as error:
            return str(error), 400
        except Exception as error:
            return f"The report could not read the database: {error}", 500

        if not one_report_at_a_time.acquire(timeout=max_wait):
            return "Another report is still being made, try again in a moment.", 503
        try:
            now = datetime.now()
            letters = "".join(letter for key, (_, letter) in SECTIONS.items() if key in chosen)
            base = f"Report_{now:%Y-%m-%d}_{letters}"
            name, number = base, 1
            while find_file(name):                      # the same report asked for again today gets _2, _3, ...
                number += 1
                name = f"{base}_{number}"

            changes = {"BETWEEN_DAYS": periods["date"], "SPECIFIC_WEEK": periods["week"], "MONTH": periods["month"],
                       "REPORT_NAME": name}
            for key, (config_key, _) in SECTIONS.items():
                changes[config_key] = "True" if key in chosen else "False"
            write_config(config_path, changes)

            try:
                export_tables(data_folder, passwords_file)
            except Exception as error:                  # no database driver, wrong login, MySQL not running ...
                return f"The report could not read the database: {error}", 500

            with tempfile.TemporaryFile() as errors:
                process = subprocess.Popen([python, str(script)], cwd=project, stdout=subprocess.DEVNULL, stderr=errors)

                deadline = time.monotonic() + max_wait
                last_size = None
                size_changed_at = time.monotonic()
                exited_at = None
                while time.monotonic() < deadline:
                    found = find_file(name)
                    finished = process.poll() is not None
                    if found:
                        # The file can show up before it is complete, so it is sent once the script has ended
                        # (or, if the script lingers, once the size has not changed for 10 seconds)
                        size = found.stat().st_size
                        if size != last_size:
                            last_size, size_changed_at = size, time.monotonic()
                        if size > 0 and (finished or time.monotonic() - size_changed_at >= 10):
                            return send_file(found, as_attachment=True, download_name=found.name)
                    elif finished:
                        exited_at = exited_at or time.monotonic()
                        if time.monotonic() - exited_at > 2:               # it ended and no file came
                            break
                    time.sleep(0.5)

                if process.poll() is None:
                    process.kill()
                    process.wait()
                    return f"The report was not ready after {max_wait // 60} minutes.", 504

                errors.seek(0)
                detail = errors.read().decode("utf-8", errors="replace").strip().splitlines()
                message = detail[-1] if detail else "no error message"
                if process.returncode != 0:
                    return f"GenerateReport.py failed: {message}", 500
                return "GenerateReport.py finished but no file appeared in the Reports folder.", 500
        finally:
            one_report_at_a_time.release()
