<?php
// The gray layer and the box that tell an admin or user they are about to be logged out. js/inactivity.js shows and hides them
// and reads the timeout from data-timeout-minutes.

function renderInactivityBox(): void
    { ?>
    <div id = "gray-out">
        <div id = "inactiveBox" data-timeout-minutes="<?php echo inactivityMinutes(); ?>">
            You have been detected as inactive, you will be logged out in : <span id="inactiveCountdown"></span>
                </br>
                Move your mouse or click/tap anywhere to reactivate the page.
        </div>
    </div>
<?php
    }
