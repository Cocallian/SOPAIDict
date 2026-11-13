// Add new user form and account request form: the username and password rules, checked as you type

// A username is valid when it is "something" followed by one of the domains listed under "org_names" in FrontEndConfig.json
function validateUsername(name, domains)
{
    name = name.trim().toLowerCase();
    for (const domain of domains) {
        const d = domain.toLowerCase();
        if (name.endsWith(d) && /^[^\s@]+$/.test(name.slice(0, name.length - d.length))) return true;
    }
    return false;
}

// The allowed domains travel on the username input as data-domains (filled in by PHP from every line under [org_name])
// Checked as you type, and again on submit
function checkUsername()
{
    const input = document.getElementById("new-username");
    const domains = JSON.parse(input.dataset.domains);
    input.setCustomValidity(validateUsername(input.value, domains) ? "" : "The username must be an email ending in: " + domains.join(", "));
}

if (document.getElementById("new-username")) {
    document.getElementById("new-username").addEventListener("input", checkUsername);
    document.getElementById("new-user-form").addEventListener("submit", function (e) {
        checkUsername();
        if (!document.getElementById("new-username").checkValidity()) {
            e.preventDefault();
            document.getElementById("new-username").reportValidity();
        }
    });
    checkUsername();
}

// One rule per <li> in #password-rules, in the same order
const passwordRules = [
    /.{8,}/,
    /[0-9]/,
    /[a-z]/,
    /[A-Z]/,
    /[^A-Za-z0-9]/
];

// Ticks off the checklist for the typed password, returns true when every rule passes
function ValidatePassword(password)
{
    const items = document.querySelectorAll("#password-rules li");
    let valid = true;
    passwordRules.forEach(function (rule, i) {
        const ok = rule.test(password);
        items[i].classList.toggle("checked", ok);
        if (!ok) valid = false;
    });
    return valid;
}

if (document.getElementById("new-password")) {
    document.getElementById("new-password").addEventListener("input", function () {
        const pass = document.getElementById("new-password");
        const confirm = document.getElementById("confirm-password");
        pass.setCustomValidity(ValidatePassword(pass.value) ? "" : "The password doesn't meet every rule in the list");
        confirm.setCustomValidity(confirm.value === pass.value ? "" : "The passwords don't match");
    });
    document.getElementById("confirm-password").addEventListener("input", function () {
        const pass = document.getElementById("new-password");
        const confirm = document.getElementById("confirm-password");
        confirm.setCustomValidity(confirm.value === pass.value ? "" : "The passwords don't match");
    });
}
