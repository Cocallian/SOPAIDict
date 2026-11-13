// Backup buttons (adminAgent.php): ask for the admin's password first, then submit the form with it as confirm-password
const backupConfirm = document.getElementById("backup-confirm");
if (backupConfirm) {
    const passwordInput = document.getElementById("backup-confirm-password");
    let pending = null;   // { form, name, value } of the backup button that was pressed

    function closeBackupConfirm() {
        backupConfirm.classList.remove("open");
        passwordInput.value = "";
        document.getElementById("backup-confirm-error").textContent = "";
        pending = null;
    }

    document.querySelectorAll('button[name^="backup"]').forEach(function (button) {
        button.addEventListener("click", function (e) {
            e.preventDefault();   // the form is only submitted after the password is entered
            pending = { form: button.form, name: button.name, value: button.value };
            backupConfirm.classList.add("open");
            passwordInput.focus();
        });
    });

    // The throbber in the loading popup until the page reloads with the result
    function throbBackup(target, message) {
        throbber(function (frame) { target.textContent = message + " " + frame; });
    }

    // Checks the password first. A wrong one is shown in the box; a right one hides the box and the dashboard behind the
    // loading popup (Google Drive backups only) and then sends the form
    async function submitBackup() {
        if (!pending || passwordInput.value === "") { return; }
        const error = document.getElementById("backup-confirm-error");
        error.textContent = "";

        let answer = "no";
        try {
            const reply = await fetch("adminAgent.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ "check-backup-password": "1", "confirm-password": passwordInput.value })
            });
            answer = (await reply.text()).trim();
        } catch (problem) {
            error.textContent = "Couldn't reach the server, try again";
            return;
        }
        if (answer !== "ok") {
            error.textContent = "Incorrect password";
            passwordInput.select();
            return;
        }

        const toDrive = pending.name.endsWith("drive");
        const messages = {
            "backup": "Backing up the database...",
            "backup-system": "Zipping the system...",
            "backup-drive": "Backing up the database to Google Drive...",
            "backup-system-drive": "Backing up the system to Google Drive..."
        };
        backupConfirm.classList.remove("open");
        document.getElementById("backup-loading").classList.add("open");
        document.getElementById("backup-loading-note").style.display = toDrive ? "" : "none";   // the Google sign in note
        throbBackup(document.getElementById("backup-loading-text"), messages[pending.name]);
        await sleep(100);                               // let the popup draw before the page starts waiting for the server

        if (!toDrive) {
            // Local backups come back as a file: wait for it here, save it in this browser, then say so
            try {
                const reply = await fetch("adminAgent.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ [pending.name]: pending.value, "confirm-password": passwordInput.value })
                });
                if (!reply.ok) { location.reload(); return; }   // the page shows the error popup the server set

                const name = ((reply.headers.get("Content-Disposition") || "").match(/filename="?([^";]+)"?/) || [])[1] || "backup";
                const url = URL.createObjectURL(await reply.blob());
                const link = document.createElement("a");
                link.href = url;
                link.download = name;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function () { URL.revokeObjectURL(url); }, 10000);

                document.getElementById("backup-loading").classList.remove("open");
                closeBackupConfirm();
                const popup = document.createElement("div");
                popup.id = "successPopup";
                popup.textContent = "Backup received: " + name;
                const close = document.createElement("button");
                close.type = "button";
                close.setAttribute("aria-label", "Close");
                close.innerHTML = "&times;";
                close.addEventListener("click", function () { popup.remove(); });
                popup.appendChild(close);
                document.body.appendChild(popup);
                setTimeout(function () { popup.remove(); }, 7000);
            } catch (problem) {
                location.reload();
            }
            return;
        }

        for (const [name, value] of [[pending.name, pending.value], ["confirm-password", passwordInput.value]]) {
            const field = document.createElement("input");
            field.type = "hidden";
            field.name = name;
            field.value = value;
            pending.form.appendChild(field);
        }
        pending.form.submit();
    }

    document.getElementById("backup-confirm-ok").addEventListener("click", submitBackup);
    document.getElementById("backup-confirm-cancel").addEventListener("click", closeBackupConfirm);
    passwordInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") { submitBackup(); }
        if (e.key === "Escape") { closeBackupConfirm(); }
    });
}
