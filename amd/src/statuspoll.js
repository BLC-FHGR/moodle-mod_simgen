// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Polls the generation status and reloads the page as soon as it changes.
 *
 * NOTE: amd/build/statuspoll.min.js is a hand-written AMD version of this file. Rebuild it with grunt after changes.
 *
 * @module     mod_simgen/statuspoll
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Starts polling.
 *
 * @param {Number} cmid course module id
 * @param {Number} status current status
 * @param {String} elapsedregion selector of an element showing the elapsed time
 * @param {Number} since timestamp (seconds) the generation was requested
 */
export const init = (cmid, status, elapsedregion, since) => {
    const elapsed = elapsedregion ? document.querySelector(elapsedregion) : null;
    if (elapsed && since) {
        const tick = () => {
            const seconds = Math.max(0, Math.round(Date.now() / 1000 - since));
            elapsed.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
        };
        tick();
        setInterval(tick, 1000);
    }

    const poll = () => {
        Ajax.call([{methodname: 'mod_simgen_get_status', args: {cmid}}])[0]
            .then((result) => {
                if (result.status !== status) {
                    // Reload without a selected version, so the newly generated version is shown.
                    const url = new URL(window.location.href);
                    url.searchParams.delete('version');
                    window.location.href = url.toString();
                } else {
                    setTimeout(poll, 5000);
                }
                return null;
            })
            .catch(() => setTimeout(poll, 15000));
    };
    setTimeout(poll, 5000);
};
