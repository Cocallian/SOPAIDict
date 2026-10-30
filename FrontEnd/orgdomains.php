<?php
// Reads every value under [org_name] in passwords.txt, whatever each line's key is called.
// The file is read line by line, because parse_ini_file keeps only the last of several lines with the same key.
function loadOrgDomains()
    {
        $domains = [];
        $inside = false;
        foreach(file(__DIR__ . "/passwords.txt", FILE_IGNORE_NEW_LINES) as $line)
            {
                $line = trim($line);
                if($line === "" || $line[0] === ";") { continue; }
                if($line[0] === "[") { $inside = strtolower($line) === "[org_names]"; continue; }
                if($inside && str_contains($line, "="))
                    {
                        $value = trim(explode("=", $line, 2)[1], " \t\"'");
                        if($value !== "") { $domains[] = $value[0] === "@" ? $value : "@" . $value; }
                    }
            }
        return $domains;
    }

// A valid username is "something" followed by one of the domains, e.g. jane@sahpra.co.za
function validOrgUsername($name, $domains)
    {
        foreach($domains as $domain)
            {
                if(str_ends_with(strtolower($name), strtolower($domain))
                   && preg_match('/^[^\s@]+$/', substr($name, 0, strlen($name) - strlen($domain)))) { return true; }
            }
        return false;
    }
