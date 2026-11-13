<?php
// The limits of the Generate report form: nothing before the oldest entry in the database and nothing after today.
// js/reports.js checks the same limits in the browser (from data-earliest and data-today), the Flask server checks them again.

// The date of the oldest entry in the tables a report is built from. Today when there is none (or no database).
function loadEarliestEntry(?mysqli $conn, string $today): string
    {
        $earliest = $today;
        if($conn)
            {
                try
                    {
                        $oldest = $conn->query("SELECT MIN(t) FROM (SELECT MIN(CreatedAt) AS t FROM Prompts UNION ALL SELECT MIN(CreatedAt) FROM Responses
                                                UNION ALL SELECT MIN(SourcedAt) FROM SourcedDocuments UNION ALL SELECT MIN(LoggedAt) FROM Logs) AS oldest")->fetch_row()[0];
                        if($oldest) { $earliest = substr($oldest, 0, 10); }
                    }
                catch(mysqli_sql_exception) {}
            }
        return $earliest;
    }

// The last 12 weeks (Monday to Sunday) that can hold data, newest first: this week, last week, then "2 weeks ago" and so on
function selectableWeeks(string $earliest): array
    {
        $thisMonday = strtotime("monday this week");
        $weeks = [];
        for($i = 0; $i < 12; $i++)
            {
                $start = strtotime("-$i week", $thisMonday);
                $label = $i === 0 ? "This week" : ($i === 1 ? "Last week" : "$i weeks ago");
                $weeks[] = ["value" => date("Y-m-d", $start),
                            "label" => $label . " (" . date("j M", $start) . " - " . date("j M", strtotime("+6 day", $start)) . ")"];
            }
        return array_values(array_filter($weeks, fn($week) => date("Y-m-d", strtotime("+6 day", strtotime($week["value"]))) >= $earliest));   // weeks that can hold data
    }

// The first year of the year list: four years back, but no earlier than the oldest entry
function firstSelectableYear(string $earliest): int
    {
        return max((int)date("Y") - 4, (int)substr($earliest, 0, 4));
    }

// The four things a report can include, as switch name => label
function reportSections(): array
    {
        return [
            "total"    => "Total responses",
            "busiest"  => "Busiest days",
            "sourced"  => "Most sourced",
            "sessions" => "Longest sessions",
        ];
    }
