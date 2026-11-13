// Login page: block submission unless both fields have a non-blank value
const loginForm = document.getElementById("loginForm");
if (loginForm) {
    const uname = document.getElementById("uname");
    const pass = document.getElementById("pass");
    const fieldError = document.getElementById("fieldError");

    loginForm.addEventListener("submit", function (e) {
        let valid = true;

        [uname, pass].forEach(function (input) {
            if (input.value == null || input.value.trim() === "") {
                input.classList.add("invalid");
                valid = false;
            } else {
                input.classList.remove("invalid");
            }
        });

        if (!valid) {
            e.preventDefault();
            fieldError.textContent = "Please enter your username and password.";
        } else {
            fieldError.textContent = "";
        }
    });

    // Clear the error for a field as soon as the user types in it
    [uname, pass].forEach(function (input) {
        input.addEventListener("input", function () {
            if (input.value.trim() !== "") {
                input.classList.remove("invalid");
            }
        });
    });
}
