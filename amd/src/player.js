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
    ]);
    const [sSaving, sSaved, sSaveError, sCompleted, sResetTitle, sResetConfirm, sReset, sPrefill] = str;

    const setStatus = (text) => {
        if (statusEl) {
            statusEl.textContent = text;
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

    // Saving (serialised; while the page is being left a beacon is used instead of AJAX).
    let saving = false;
    let pending = null;

    const beaconSave = (state) => {
        if (!navigator.sendBeacon) {
            return false;
        }
        const url = Config.wwwroot + '/lib/ajax/service.php?sesskey=' + Config.sesskey + '&info=mod_simgen_save_state';
        const body = JSON.stringify([{index: 0, methodname: 'mod_simgen_save_state', args: {cmid: opts.cmid, state}}]);
        return navigator.sendBeacon(url, new Blob([body], {type: 'application/json'}));
    };

    const doSave = () => {
        if (!pending) {
            return;
        }
        const state = pending;
        pending = null;
        saving = true;
        setStatus(sSaving);
        // Note: Ajax.call returns jQuery promises, which have no finally().
        call('mod_simgen_save_state', {cmid: opts.cmid, state})
            .then(() => setStatus(sSaved.replace('{$a}', timeNow())))
            .catch(() => setStatus(sSaveError))
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
        const args = {cmid: opts.cmid, completed: true, summary: String(d.summary || '').slice(0, 4000)};
        if (persist && typeof d.state === 'string') {
            args.state = d.state;
        }
        if (typeof d.score === 'number' && isFinite(d.score)) {
            args.score = d.score;
        }
        if (typeof d.maxScore === 'number' && isFinite(d.maxScore)) {
            args.maxscore = d.maxScore;
        }
        call('mod_simgen_save_state', args)
            .then(() => {
                setStatus(sCompleted);
                return addToast(sCompleted);
            })
            .catch(Notification.exception);
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
        }
        call(methodname, args)
            .then((result) => send({type: 'response', requestId: d.requestId, ok: true, data: conversationResult(result)}))
            .catch((e) => send({
                type: 'response',
                requestId: d.requestId,
                ok: false,
                error: (e && (e.message || e.error)) || String(e),
            }));
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
                queueSave(d.state);
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
            case 'reset':
                resetProgress();
                break;
            case 'reload':
                reloadFrame();
                break;
            case 'conversation-load':
            case 'converse':
            case 'conversation-reset':
                handleConversation(d);
                break;
        }
    });

    // Ask the simulation to flush unsaved changes when the page is left.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && frame.contentWindow) {
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
