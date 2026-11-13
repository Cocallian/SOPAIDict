// Feedback window beside the answer: saves the score when it is picked and the score plus the comments on Send.
// Nothing touched leaves the "N/A" feedback.php already stored.
var feedbackBox = document.getElementById("feedback");
if (feedbackBox) {
    var fbRating = document.getElementById("fbRating");
    var fbText = document.getElementById("fbText");
    var fbSend = document.getElementById("fbSend");
    var fbStatus = document.getElementById("fbStatus");

    function saveFeedback(final)
    {
        return fetch(window.location.pathname, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded", "X-CSRF-Token": csrfToken()},
            body: new URLSearchParams({feedback_id: feedbackBox.dataset.feedbackId, rating: fbRating.value, extra: fbText.value})
        }).then(function (reply) {
            if (!reply.ok) throw new Error();
            fbStatus.textContent = final ? "Thank you for your feedback!" : "Score saved";
            return true;
        }).catch(function () {
            fbStatus.textContent = "Feedback could not be saved";
            return false;
        });
    }

    fbRating.addEventListener("change", function () { saveFeedback(false); });
    fbSend.addEventListener("click", function () {
        fbSend.hidden = true;   // hidden at once so it can't be pressed twice
        saveFeedback(true).then(function (saved) {
            if (saved) {
                fbRating.disabled = true;
                fbText.disabled = true;
            } else {
                fbSend.hidden = false;   // nothing was saved, allow another try
            }
        });
    });
}
