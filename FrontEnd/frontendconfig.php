<?php
const FRONTEND_CONFIG_FILE = __DIR__ . "/FRONTCONFIG.json";

// The whole file as an array, empty when it is missing or isn't valid JSON
function readFrontEndConfig()
    {
        if(!is_file(FRONTEND_CONFIG_FILE)) { return []; }
        $config = json_decode(file_get_contents(FRONTEND_CONFIG_FILE), true);
        return is_array($config) ? $config : [];
    }

// Every setting from every section in one flat array (db_host, db_user, db_pass, colab_url, server_path, ...),
// which is what the pages call $secrets. org_names is a list, so it is left out; loadOrgDomains() reads it.
function loadSecrets()
    {
        $flat = [];
        foreach(readFrontEndConfig() as $values)
            {
                if(is_array($values) && !array_is_list($values)) { $flat = array_merge($flat, $values); }
            }
        return $flat;
    }

// Minutes of inactivity before a session is logged out: "inactivity_timeout_minutes" in FrontEndConfig.json, 15 when it isn't set
function inactivityMinutes()
    {
        $minutes = loadSecrets()["inactivity_timeout_minutes"] ?? 15;
        return (is_numeric($minutes) && $minutes > 0) ? $minutes + 0 : 15;
    }

function saveFrontEndConfig($config)
    {
        file_put_contents(FRONTEND_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n", LOCK_EX);
    }
