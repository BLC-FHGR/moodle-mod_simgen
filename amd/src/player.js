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
 * Player of mod_simgen: connects the sandboxed simulation iframe with Moodle.
 *
 * The iframe talks to this module via postMessage (hello, save, complete, resize, error, reset, reload).
 * This module persists the progress through web services and only ever for the current user.
 *
 * NOTE: amd/build/player.min.js is a hand-written AMD version of this file. Rebuild it with grunt after changes.
 *
 * @module     mod_simgen/player
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Config from 'core/config';
import Notification from 'core/notification';
import {get_strings as getStrings} from 'core/str';
import {add as addToast} from 'core/toast';

const call = (methodname, args) => Ajax.call([{methodname, args}])[0];

/**
 * Initialises the player.
 *
 * @param {Object} opts
 * @param {Number} opts.cmid course module id
 * @param {String} opts.wrapperid id of the player wrapper
 * @param {Boolean} opts.savestate persist progress
 * @param {Boolean} opts.cansave user may save (not a guest)
 * @param {Boolean} opts.canmanage user is a teacher (show errors with "fix with AI")
 * @param {Number} opts.minheight minimal iframe height in px
 * @param {Number} opts.versionid shown version (the server only accepts drafts for teachers)
 * @param {String} opts.firstname first name of the user (offered to the simulation)
 * @param {Boolean} opts.check report the start-up check of this version (teachers, version not checked/compiled yet)
 */
export const init = async(opts) => {
    const wrapper = document.getElementById(opts.wrapperid);
    if (!wrapper) {
        return;
    }
    const frame = wrapper.querySelector('iframe');
    const statusEl = wrapper.querySelector('[data-region="savestatus"]');
    const errorRegion = document.querySelector('[data-region="simgen-errorreport"]');
    const persist = !!(opts.savestate && opts.cansave);

    const str = await getStrings([
        {key: 'player_saving', component: 'mod_simgen'},
        {key: 'player_saved', component: 'mod_simgen'},
        {key: 'player_saveerror', component: 'mod_simgen'},
        {key: 'player_completed', component: 'mod_simgen'},
        {key: 'player_resettitle', component: 'mod_simgen'},
        {key: 'player_resetconfirm', component: 'mod_simgen'},
        {key: 'player_reset', component: 'mod_simgen'},
        {key: 'error_prefill', component: 'mod_simgen'},
        {key: 'check_badge_passed', component: 'mod_simgen'},
        {key: 'check_badge_failed', component: 'mod_simgen'},
        {key: 'check_repairqueued', component: 'mod_simgen'},
        {key: 'error_versionchanged', component: 'mod_simgen'},
    ]);
    const [sSaving, sSaved, sSaveError, sCompleted, sResetTitle, sResetConfirm, sReset, sPrefill,
        sCheckPassed, sCheckFailed, sRepairQueued, sVersionChanged] = str;

    const setStatus = (text) => {
        if (statusEl) {
            statusEl.textContent = text;
        }
    };

    // A newer version was published while the page was open: the server no longer stores the state of this code.
    let versionNoticeShown = false;
    const versionChanged = () => {
        setStatus(sVersionChanged);
        if (!versionNoticeShown) {
            versionNoticeShown = true;
            addToast(sVersionChanged, {type: 'warning', autohide: false, closeButton: true}).catch(() => null);
        }
    };
    const timeNow = () => new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});

    // State loading (cached per iframe load, the iframe repeats "hello" until it gets an answer).
    let statePromise = null;
    const loadState = () => {
        if (!statePromise) {
            statePromise = persist
                ? call('mod_simgen_get_state', {cmid: opts.cmid}).catch(() => ({state: '', completed: false}))
                : Promise.resolve({state: '', completed: false});
        }
        return statePromise;
    };
    loadState();

    const send = (msg) => {
        msg.source = 'simgen-host';
        // The iframe has an opaque origin, therefore '*'. Only the user's own data is sent.
        frame.contentWindow.postMessage(msg, '*');
    };

    const handleHello = async() => {
        const result = await loadState();
        send({
            type: 'init',
            state: result.state || null,
            completed: !!result.completed,
            savestate: persist,
            user: {firstname: opts.firstname || ''},
        });
    };

    // Active working time: counted while the page is visible and the learner was active in the last two minutes;
    // sent along with the next save (or on page leave / completion).
    const IDLE_AFTER = 120000;
    const TICK = 5;
    let lastActivity = Date.now();
    let activeSeconds = 0;
    if (opts.cansave) {
        setInterval(() => {
            if (document.visibilityState === 'visible' && Date.now() - lastActivity < IDLE_AFTER) {
                activeSeconds += TICK;
            }
        }, TICK * 1000);
    }
    const takeActiveSeconds = () => {
        const seconds = activeSeconds;
        activeSeconds = 0;
        return seconds;
    };

    // Saving (serialised; while the page is being left a beacon is used instead of AJAX).
    let saving = false;
    let pending = null;

    const beaconSave = (state) => {
        if (!navigator.sendBeacon) {
            return false;
        }
        const url = Config.wwwroot + '/lib/ajax/service.php?sesskey=' + Config.sesskey + '&info=mod_simgen_save_state';
        const seconds = takeActiveSeconds();
        const args = {cmid: opts.cmid, state, activeseconds: seconds, versionid: opts.versionid || 0};
        const body = JSON.stringify([{index: 0, methodname: 'mod_simgen_save_state', args}]);
        if (navigator.sendBeacon(url, new Blob([body], {type: 'application/json'}))) {
            return true;
        }
        activeSeconds += seconds;
        return false;
    };

    const doSave = () => {
        if (!pending) {
            return;
        }
        const state = pending;
        pending = null;
        saving = true;
        setStatus(sSaving);
        const seconds = takeActiveSeconds();
        // Note: Ajax.call returns jQuery promises, which have no finally().
        call('mod_simgen_save_state', {cmid: opts.cmid, state, activeseconds: seconds, versionid: opts.versionid || 0})
            .then((result) => {
                if (result.versionchanged) {
                    versionChanged();
                } else {
                    setStatus(sSaved.replace('{$a}', timeNow()));
                }
                return null;
            })
            .catch(() => {
                activeSeconds += seconds;
                setStatus(sSaveError);
            })
            .then(() => {
                saving = false;
                doSave();
                return null;
            });
    };

    const queueSave = (state) => {
        if (!persist || typeof state !== 'string') {
            return;
        }
        statePromise = Promise.resolve({state, completed: false});
        if (document.visibilityState === 'hidden' && beaconSave(state)) {
            return;
        }
        pending = state;
        if (!saving) {
            doSave();
        }
    };

    const handleComplete = (d) => {
        if (!opts.cansave) {
            return;
        }
        const args = {cmid: opts.cmid, completed: true, summary: String(d.summary || '').slice(0, 4000),
            versionid: opts.versionid || 0};
        if (persist && typeof d.state === 'string') {
            args.state = d.state;
        }
        if (typeof d.score === 'number' && isFinite(d.score)) {
            args.score = d.score;
        }
        if (typeof d.maxScore === 'number' && isFinite(d.maxScore)) {
            args.maxscore = d.maxScore;
        }
        args.activeseconds = takeActiveSeconds();
        call('mod_simgen_save_state', args)
            .then((result) => {
                setStatus(sCompleted);
                if (result.versionchanged) {
                    versionChanged();
                }
                return addToast(sCompleted);
            })
            .catch((e) => {
                activeSeconds += args.activeseconds;
                Notification.exception(e);
            });
    };

    const resize = (height) => {
        if (typeof height !== 'number' || !isFinite(height) || document.fullscreenElement === wrapper) {
            return;
        }
        frame.style.height = Math.max(opts.minheight, Math.min(Math.ceil(height) + 4, 20000)) + 'px';
    };

    const reloadFrame = () => {
        statePromise = null;
        const url = new URL(frame.src, window.location.href);
        url.searchParams.set('_', Date.now());
        frame.src = url.toString();
    };

    const resetProgress = async() => {
        try {
            await Notification.saveCancelPromise(sResetTitle, sResetConfirm, sReset);
        } catch (e) {
            return; // Cancelled.
        }
        pending = null;
        try {
            if (persist) {
                await call('mod_simgen_reset_state', {cmid: opts.cmid});
            }
            setStatus('');
            reloadFrame();
        } catch (e) {
            Notification.exception(e);
        }
    };

    // Live conversations with AI personas: forward the request to the web services and answer the iframe.
    const conversationResult = (result) => ({
        reply: result.reply || '',
        messages: JSON.parse(result.messages || '[]'),
        signals: JSON.parse(result.signals || '{}'),
        turn: result.turn,
        maxTurns: result.maxturns,
        ended: !!result.ended,
        done: !!result.done,
    });

    const handleConversation = (d) => {
        const args = {cmid: opts.cmid, versionid: opts.versionid || 0, personaid: String(d.personaId || '')};
        let methodname = 'mod_simgen_get_conversation';
        if (d.type === 'converse') {
            methodname = 'mod_simgen_converse';
            args.message = String(d.message || '');
        } else if (d.type === 'conversation-reset') {
            methodname = 'mod_simgen_reset_conversation';
        } else if (d.type === 'conversation-end') {
            methodname = 'mod_simgen_end_conversation';
        }
        call(methodname, args)
            .then((result) => send({type: 'response', requestId: d.requestId, ok: true, data: conversationResult(result)}))
            .catch((e) => {
                const error = (e && (e.message || e.error)) || String(e);
                // Always visible, even if the generated user interface does not show chat.error.
                showNotice(error);
                send({type: 'response', requestId: d.requestId, ok: false, error});
            });
    };

    const showNotice = (message) => {
        addToast(String(message || '').slice(0, 1000), {type: 'danger', autohide: true, delay: 15000, closeButton: true})
            .catch(() => null);
    };

    const handleError = (d) => {
        if (!opts.canmanage || !errorRegion) {
            return;
        }
        const message = String(d.message || '') + (d.stack ? '\n\n' + String(d.stack) : '');
        const out = errorRegion.querySelector('[data-region="errormessage"]');
        if (out) {
            out.textContent = message;
        }
        const prefill = errorRegion.querySelector('input[name="prefill"]');
        if (prefill) {
            prefill.value = sPrefill.replace('{$a}', message).slice(0, 6000);
        }
        errorRegion.hidden = false;
    };

    // Start-up check of a new version: store the result (and the compiled code); a failed AI version is repaired.
    let checkReported = false;
    const handleCheck = (d) => {
        if (!opts.check || checkReported) {
            return;
        }
        checkReported = true;
        const args = {cmid: opts.cmid, versionid: opts.versionid, ok: !!d.ok, error: String(d.error || '').slice(0, 6000)};
        if (d.ok && typeof d.compiled === 'string') {
            args.compiled = d.compiled;
            args.appname = String(d.appName || 'App');
            args.selfrender = !!d.selfRender;
        }
        call('mod_simgen_report_check', args)
            .then((result) => {
                const badge = document.querySelector('[data-region="simgen-checkstatus"]');
                if (badge) {
                    badge.textContent = result.status === 1 ? sCheckPassed : sCheckFailed;
                    badge.classList.toggle('bg-success', result.status === 1);
                    badge.classList.toggle('bg-danger', result.status !== 1);
                    badge.classList.remove('bg-secondary');
                }
                if (result.repairqueued) {
                    addToast(sRepairQueued, {type: 'info'}).catch(() => null);
                    // Show the generation status (and afterwards the repaired version).
                    setTimeout(() => {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('version');
                        window.location.href = url.toString();
                    }, 2500);
                }
                return null;
            })
            .catch(() => {
                checkReported = false;
            });
    };

    window.addEventListener('message', (ev) => {
        if (ev.source !== frame.contentWindow) {
            return;
        }
        const d = ev.data;
        if (!d || typeof d !== 'object' || d.source !== 'simgen') {
            return;
        }
        switch (d.type) {
            case 'hello':
                handleHello();
                break;
            case 'save':
                lastActivity = Date.now();
                queueSave(d.state);
                break;
            case 'activity':
                lastActivity = Date.now();
                break;
            case 'complete':
                handleComplete(d);
                break;
            case 'resize':
                resize(d.height);
                break;
            case 'error':
                handleError(d);
                break;
            case 'check':
                handleCheck(d);
                break;
            case 'reset':
                resetProgress();
                break;
            case 'reload':
                reloadFrame();
                break;
            case 'conversation-load':
            case 'converse':
            case 'conversation-reset':
            case 'conversation-end':
                handleConversation(d);
                break;
            case 'notify':
                showNotice(d.message);
                break;
        }
    });

    // Ask the simulation to flush unsaved changes when the page is left.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && frame.contentWindow) {
            // The working time is sent now; a state saved by the flush follows separately.
            if (opts.cansave && activeSeconds > 0) {
                beaconSave('');
            }
            send({type: 'flush'});
        }
    });

    wrapper.addEventListener('click', (e) => {
        const action = e.target.closest('[data-action]');
        if (!action) {
            return;
        }
        if (action.dataset.action === 'fullscreen') {
            e.preventDefault();
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else if (wrapper.requestFullscreen) {
                wrapper.requestFullscreen();
            }
        } else if (action.dataset.action === 'reset') {
            e.preventDefault();
            resetProgress();
        } else if (action.dataset.action === 'reload') {
            e.preventDefault();
            reloadFrame();
        }
    });
};
