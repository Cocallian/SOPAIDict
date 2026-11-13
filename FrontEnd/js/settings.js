// Settings page: checks every input against the limits settings.php wrote on it (data-kind, data-min, data-max), shows each
// problem under its input, and sends the form with fetch only when every input is right.
const settingsForm = document.getElementById("settings-form");
if (settingsForm) {
    const settingPatterns = {
        path:  /^([A-Za-z]:[\\/]|\\\\)[^<>"|?*]*$/,
        model: /^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/,
        float: /^\d+(\.\d+)?$/,
        int:   /^\d+$/
    };

    // The problem with one input, "" when it is fine
    function settingProblem(input)
    {
        const value = input.value.trim();
        const label = input.dataset.label;
        const kind = input.dataset.kind;
        const min = parseFloat(input.dataset.min);
        const max = parseFloat(input.dataset.max);

        if (value === "") return input.dataset.optional ? "" : label + " can't be empty.";
        if ((kind === "text" || kind === "path" || kind === "model") && value.length > max) return label + " can be at most " + max + " characters.";
        if (kind === "int" || kind === "float") {
            if (!settingPatterns[kind].test(value)) return label + (kind === "int" ? " must be a whole number." : " must be a number, like 0.1.");
            if (parseFloat(value) < min || parseFloat(value) > max) return label + " must be between " + min + " and " + max + ".";
        } else if (kind === "path") {
            if (!settingPatterns.path.test(value)) return label + " must be a full Windows path, like D:\\Backups.";
        } else if (kind === "model") {
            if (!settingPatterns.model.test(value)) return label + " can only have letters, numbers and . _ : / -";
        } else if (/[\x00-\x1F]/.test(value)) {
            return label + " can't have control characters.";
        }
        return "";
    }

    function showSettingProblem(input)
    {
        const problem = settingProblem(input);
        const message = settingsForm.querySelector('[data-error="' + input.name + '"]');
        message.textContent = problem;
        message.hidden = problem === "";
        input.closest(".setting-block").classList.toggle("invalid", problem !== "");
        return problem === "";
    }

    const settingInputs = Array.from(settingsForm.querySelectorAll('input[type="text"]'));
    settingInputs.forEach(function (input) {
        input.addEventListener("blur", function () { showSettingProblem(input); });
        input.addEventListener("input", function () {
            if (input.closest(".setting-block").classList.contains("invalid")) showSettingProblem(input);   // clears the message as it is fixed
        });
    });

    settingsForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        const status = document.getElementById("settings-status");
        const wrong = settingInputs.filter(function (input) { return !showSettingProblem(input); });
        if (wrong.length > 0) {                          // nothing is sent until every input is right
            wrong[0].scrollIntoView({block: "center", behavior: "smooth"});
            status.className = "settings-status failed";
            status.textContent = "Fix the highlighted settings and try again.";
            status.hidden = false;
            return;
        }

        const button = settingsForm.querySelector(".save-btn");
        const data = new FormData(settingsForm);
        data.append("save-settings", "save-settings");
        button.disabled = true;
        try {
            const reply = await fetch("settings.php", {method: "POST", body: data, headers: {"X-Requested-With": "fetch"}});
            const text = (await reply.text()).trim();
            if (!reply.ok) throw new Error(text || "The settings could not be saved.");
            status.className = "settings-status done";
            status.textContent = text;
        } catch (error) {
            status.className = "settings-status failed";
            status.textContent = error.message;
        } finally {
            status.hidden = false;
            button.disabled = false;
        }
    });
}
