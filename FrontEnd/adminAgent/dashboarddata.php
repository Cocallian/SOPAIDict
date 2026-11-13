<?php
// What the dashboard on the admin page shows: the query graphs, the active users and all users

use CpChart\Data;
use CpChart\Image;

// The periods the graph dropdown offers. They are rolling windows that end today: the last 7, 30 and 365 days.
const GRAPH_PERIODS = ["7days", "30days", "365days"];

// How one period is drawn: where the SQL starts, how the rows are grouped, the x axis (bucket => label, where the bucket
// is the text the SQL GROUP BY gives for a row) and the titles
function periodDefinition(string $period): array
    {
        $axis = [];
        if($period == "365days")
            {
                // By month: the first month is only partly inside the 365 days
                $month = new DateTime(date("Y-m-01", strtotime("-364 day")));
                while($month->format("Y-m") <= date("Y-m"))
                    {
                        $axis[$month->format("Y-m")] = $month->format("M y");
                        $month->modify("+1 month");
                    }
                return ["from" => "DATE_SUB(CURDATE(), INTERVAL 364 DAY)", "group" => "DATE_FORMAT(CreatedAt, '%Y-%m')", "axis" => $axis,
                        "title" => "Queries in the last 365 days (by month)", "xName" => "Month"];
            }

        $days = $period == "7days" ? 7 : 30;
        for($back = $days - 1; $back >= 0; $back--)
            {
                $day = strtotime("-$back day");
                $axis[date("Y-m-d", $day)] = $days == 7 ? date("D j", $day) : date("j M", $day);
            }
        return ["from" => "DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY)", "group" => "DATE_FORMAT(CreatedAt, '%Y-%m-%d')", "axis" => $axis,
                "title" => "Queries in the last $days days (by day)", "xName" => "Day"];
    }

// The number of queries in each bucket of the axis, and how many people asked and how many questions there were in total
function loadPeriodCounts(?mysqli $conn, array $definition): array
    {
        $labels = [];
        $counts = [];
        $people = 0;
        $total = 0;

        if($conn)
            {
                $found = [];
                $res = $conn->query("SELECT {$definition["group"]} AS bucket, COUNT(*) AS total
                                     FROM Prompts
                                     WHERE CreatedAt >= {$definition["from"]}
                                     GROUP BY bucket");
                while($row = $res->fetch_assoc()) { $found[$row["bucket"]] = (int)$row["total"]; }

                foreach($definition["axis"] as $bucket => $label)
                    {
                        $labels[] = $label;
                        $counts[] = $found[$bucket] ?? 0;
                    }

                $res = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT UserId) AS users, COUNT(DISTINCT AdminId) AS admins
                                     FROM Prompts
                                     WHERE CreatedAt >= {$definition["from"]}");
                $row = $res->fetch_assoc();
                $people = (int)$row["users"] + (int)$row["admins"];
                $total = (int)$row["total"];
            }

        return [$labels, $counts, $people, $total];
    }

// The line graph as a base64 PNG for an <img>, "" when there is nothing to draw
function drawQueriesChart(array $counts, array $labels, string $title, string $xName): string
    {
        if(array_sum($counts) <= 0) { return ""; }

        $chart = new Data();
        $chart->addPoints($counts, "Queries");
        $chart->addPoints($labels, "Labels");
        $chart->setAbscissa("Labels");
        $chart->setSerieDescription("Queries", $title);
        $chart->setPalette("Queries", ["R" => 255, "G" => 193, "B" => 7]);
        $chart->setAxisName(0, "Number of queries");
        $chart->setAbscissaName($xName);

        $nonZero = [];
        foreach($counts as $c) { $nonZero[] = $c > 0 ? $c : VOID; }
        $chart->addPoints($nonZero, "QueryLabels");
        $chart->setPalette("QueryLabels", ["R" => 255, "G" => 193, "B" => 7]);

        $img = new Image(1600, 170, $chart);   // shown at this exact size, wider than the box so the box scrolls sideways
        $img->drawFilledRectangle(0, 0, 1600, 170, ["R" => 35, "G" => 35, "B" => 35]);
        $img->setFontProperties(["FontName" => "MankSans.ttf", "FontSize" => 10, "R" => 255, "G" => 255, "B" => 255]);
        $img->setGraphArea(80, 15, 1580, 120);
        $chart->setSerieDrawable("QueryLabels", false);
        $img->drawScale(["GridR" => 255, "GridG" => 255, "GridB" => 255, "GridAlpha" => 40, "Mode" => SCALE_MODE_START0,
                         "DrawSubTicks" => false, "AxisR" => 255, "AxisG" => 255, "AxisB" => 255, "TickR" => 255, "TickG" => 255, "TickB" => 255]);
        $img->drawLineChart(["Weight" => 3]);
        $img->drawPlotChart(["PlotSize" => 4, "PlotBorder" => true, "BorderR" => 35, "BorderG" => 35, "BorderB" => 35]);

        $chart->setSerieDrawable("Queries", false);
        $chart->setSerieDrawable("QueryLabels", true);
        $img->drawLineChart(["DisplayValues" => true, "DisplayColor" => DISPLAY_MANUAL, "DisplayR" => 255, "DisplayG" => 255, "DisplayB" => 255,
                             "ForceColor" => true, "ForceAlpha" => 0]);

        $tmp = tempnam(sys_get_temp_dir(), "pc");
        $img->render($tmp);
        $png = base64_encode(file_get_contents($tmp));
        unlink($tmp);
        return $png;
    }

// Every period is built once here, the dashboard's dropdown just shows one and hides the rest
function buildPeriodViews(?mysqli $conn): array
    {
        $views = [];
        foreach(GRAPH_PERIODS as $period)
            {
                $definition = periodDefinition($period);
                [$labels, $counts, $people, $total] = loadPeriodCounts($conn, $definition);
                $views[$period] = ["users" => $people, "total" => $total, "title" => $definition["title"],
                                   "img" => drawQueriesChart($counts, $labels, $definition["title"], $definition["xName"])];
            }
        return $views;
    }

// Everyone logged in right now, for the Active users card
function loadActiveUsers(?mysqli $conn): array
    {
        $activeUsers = [];
        if($conn)
            {
                try
                    {
                        $res = $conn->query("SELECT SessionId, Username, LastSeen FROM ActiveUsers ORDER BY Username");
                        while($row = $res->fetch_assoc()) { $activeUsers[] = $row; }
                    }
                catch(mysqli_sql_exception) {}
            }
        return $activeUsers;
    }

// "just now", "3 min ago" for the last time a session was seen
function seenAgo($lastSeen): string
    {
        $seconds = max(0, time() - (int)$lastSeen);
        if($seconds < 60) { return "just now"; }
        return floor($seconds / 60) . " min ago";
    }

// Everyone in the Users and Admins tables, for the scrollable list under Delete User
function loadAllUsers(?mysqli $conn): array
    {
        $allUsers = [];
        if($conn)
            {
                $res = $conn->query("(SELECT Username, 'user' AS Role FROM Users) UNION ALL (SELECT Username, 'admin' AS Role FROM Admins) ORDER BY Username");
                while($row = $res->fetch_assoc()) { $allUsers[] = $row; }
            }
        return $allUsers;
    }
