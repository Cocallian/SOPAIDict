<?php
require_once __DIR__ . "/frontendconfig.php";

// The allowed email domains: the "org_names" list in FrontEndConfig.json, each one starting with an @
function loadOrgDomains()
    {
        $domains = [];
        foreach(readFrontEndConfig()["org_names"] ?? [] as $value)
            {
                $value = trim((string)$value, " \t\"'");
                if($value !== "") { $domains[] = $value[0] === "@" ? $value : "@" . $value; }
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
