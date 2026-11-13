// What every page uses: the popups and the "searching" animation of the question box

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

// This page's CSRF token (from the <meta name="csrf"> tag), which every fetch POST sends as a header
const csrfToken = () => document.querySelector('meta[name="csrf"]').content;

function CArr(arr, num)
{
    const leng = arr.length;
    var safe = ((num % leng) + leng) % leng;
    return arr[safe];
}

function startSearch(e)
{
    e.preventDefault();
    if(!document.getElementById("user-input").value.trim()) return;
    throb();
    setTimeout(() => e.target.submit(), 100);
}

// The frames of the "working" animation. One of these sets is picked at random and played over and over.
const THROBBER_FRAMES = [
    ["▖", "▘", "▝", "▗", "▖", "▘", "▝", "▗"],
    ["▞", "▚", "▞", "▚", "▞", "▚", "▞", "▚"],
    ["▙", "▛", "▜", "▟", "▙", "▛", "▜", "▟"],
    ["◣", "◤", "◥", "◢", "◣", "◤", "◥", "◢"],
    ["◱", "◰", "◳", "◲", "◱", "◰", "◳", "◲"],
    ["◒", "◐", "◓", "◑", "◒", "◐", "◓", "◑"],
    ["__", "|", "▔", " |", "__", "|", "▔", " |"],
    ["◟", "◜", "◠", "◝", "◞", "◡", "◟", "◜", "◠", "◝", "◞", "◡"]
];

// Plays the animation by calling show(frame) every 150 ms for as long as keepGoing() is true
async function throbber(show, keepGoing = () => true)
{
    while (keepGoing())
    {
        const frames = THROBBER_FRAMES[Math.floor(Math.random() * THROBBER_FRAMES.length)];
        for (let i = 0; i < frames.length * 2 && keepGoing(); i++)
        {
            show(CArr(frames, i));
            await sleep(150);
        }
    }
}

// The question box while the answer is made
function throb()
{
    const ta = document.getElementById("ta");
    ta.style.fontFamily = "Consolas, 'Courier New', monospace";
    throbber(function (frame) { ta.value = "\n Searching database and generating a response..." + frame; });
}

// Close the popups on click, or automatically after 5 seconds
for (const [id, closeId] of [["errPopup", "closePopup"], ["successPopup", "closeSuccess"]]) {
    const popup = document.getElementById(id);
    if (popup) {
        document.getElementById(closeId).addEventListener("click", function () {
            popup.classList.add("hidden");
        });
        setTimeout(function () {
            popup.classList.add("hidden");
        }, 7000);
    }
}
