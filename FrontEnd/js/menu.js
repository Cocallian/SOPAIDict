// The menu panel and the Prompt history table in it

function togglePanel(open)
{
    document.getElementById("panel").classList.toggle("open", open);
    document.getElementById("close-btn").classList.toggle("show", open);
}

// shows or hides the Prompt history table
function toggleHistory()
{
    document.getElementById("history").classList.toggle("show");
}
