import json, os, threading
from datetime import datetime
from flask import request

# Settings for the admin page: a post from the front end changes the matching keys in config.json.
# A field that is missing, null or empty leaves its key as it was. config.json keeps every value as text.

FIELDS = {                                              # name in the request -> key in config.json
    "org_name":       "ORG_NAME",
    "backup_path":    "BACKUP_PATH",
    "context_window": "CONTEXT_WINDOW",
    "temperature":    "TEMPERATURE",
    "length":         "LENGTH",
    "sources":        "SOURCES",
    "running_threads": "RUNNING_THREADS",
    "model_name":     "MODEL_NAME",
    "embed_model":    "EMBED_MODEL",
}
WHOLE_NUMBERS = ("context_window", "length", "sources", "running_threads")   # must be a whole number above 0
config_lock = threading.Lock()                          # two posts at once would overwrite each other's change


class SettingError(ValueError):
    pass                                                # a message that is fine to show the admin


def log(message):
    # One line in the server window, in the same style as the question log in LocalAIServ.py
    print(f"[{datetime.now():%H:%M:%S}] [settings] {message}", flush=True)


def clean_value(name, value):
    # The value as text for config.json, or None when it was not given (missing, null or empty)
    if value is None:
        return None
    value = str(value).strip()
    if value == "":
        return None
    if name in WHOLE_NUMBERS:
        if not value.isdigit() or int(value) < 1:
            raise SettingError(f"{name} must be a whole number above 0.")
        return str(int(value))
    if name == "temperature":
        try:
            number = float(value)
        except ValueError:
            raise SettingError("temperature must be a number.")
        if not 0 <= number <= 2:
            raise SettingError("temperature must be between 0 and 2.")
        return value
    return value


def register_settings_routes(app, project):
    config_path = project / "config.json"

    @app.get("/settings")
    def send_settings():
        # The fields POST /settings can change, as one JSON object under the same names (org_name, backup_path, ...),
        # for the front end to fill its settings form with
        with config_lock:                               # not in the middle of a change
            with open(config_path, encoding="utf-8") as f:
                config = json.load(f)
        return {name: config.get(key, "") for name, key in FIELDS.items()}

    @app.post("/settings")
    def settings():
        # The front end posts any of org_name, backup_path, context_window, temperature, length, sources, model_name
        # and embed_model, as a form or as JSON
        data = request.get_json(silent=True) if request.is_json else request.form
        if not isinstance(data, (dict, type(request.form))):
            return "The settings must be sent as a form or as JSON.", 400

        changes = {}
        try:
            for name, key in FIELDS.items():
                value = clean_value(name, data.get(name))
                if value is not None:
                    changes[key] = value
        except SettingError as error:
            return str(error), 400

        if changes:
            with config_lock:
                with open(config_path, encoding="utf-8") as f:
                    config = json.load(f)
                config.update(changes)
                temporary = config_path.with_suffix(".json.tmp")
                with open(temporary, "w", encoding="utf-8") as f:
                    json.dump(config, f, indent=4)
                os.replace(temporary, config_path)      # swap in one step, so nothing reads a half-written config.json
            log("Changed " + ", ".join(changes))

        return {"changed": list(changes)}
