/* eslint-disable */
/**
 * Runtime of mod_simgen inside the sandboxed simulation iframe.
 *
 * This file is inlined into every simulation document by \mod_simgen\local\html_builder. It provides:
 *  - window.SimBridge: load/save progress and report completion (via postMessage to the Moodle page),
 *  - window.useSimState(key, initial): a persisted variant of React.useState,
 *  - window.Formula and window.MathBoard: math building blocks (KaTeX and JSXGraph, bundled in runtime/lib),
 *  - error handling (overlay + report to the Moodle page so teachers can let the AI fix the error),
 *  - automatic height reporting,
 *  - JSX compilation (Babel) and mounting of the generated React component "App" (or the precompiled code) and
 *  - the start-up check of new versions in the teacher preview (result and compiled code go to the Moodle page).
 *
 * The document runs with an opaque origin (sandbox without allow-same-origin): it can neither read Moodle cookies
 * nor call Moodle APIs directly. All persistence goes through the parent page, which only stores data for the
 * current user and activity.
 *
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';

    var CFG = window.__SIMGEN__ || {};
    var STR = CFG.strings || {};
    var hasParent = !!(window.parent && window.parent !== window);
    var hasOwn = Object.prototype.hasOwnProperty;

    var store = {};
    var lastSent = null;
    var saveTimer = null;
    var savingEnabled = false;
    var initData = null;
    var resolveReady;
    var readyPromise = new Promise(function(resolve) {
        resolveReady = resolve;
    });

    /**
     * Sends a message to the Moodle page.
     *
     * @param {Object} msg
     */
    function post(msg) {
        if (!hasParent) {
            return;
        }
        msg.source = 'simgen';
        try {
            window.parent.postMessage(msg, CFG.parentOrigin || '*');
        } catch (e) {
            // Ignore - nothing we can do.
        }
    }

    function clone(value) {
        try {
            return JSON.parse(JSON.stringify(value));
        } catch (e) {
            return value;
        }
    }

    function toNumber(value) {
        var n = Number(value);
        return (value === null || value === undefined || value === '' || isNaN(n)) ? null : n;
    }

    // ------------------------------------------------------------------------------------------------------------
    // Error handling.
    // ------------------------------------------------------------------------------------------------------------

    var reported = 0;
    var lastReported = '';
    var fatalShown = false;

    // Automatic check of new versions (teacher preview): the first error during start-up fails the check.
    var CHECK_DELAY = 3000;
    var checkDone = false;
    var checkError = null;
    var compiledResult = null;

    /**
     * Reports the result of the start-up check to the Moodle page (once), with the compiled code if it ran without
     * errors, so students do not need to load and run Babel.
     */
    function concludeCheck() {
        if (checkDone || !CFG.check) {
            return;
        }
        checkDone = true;
        var msg = {type: 'check', ok: !checkError, error: checkError || ''};
        if (!checkError && compiledResult) {
            msg.compiled = compiledResult.code;
            msg.appName = compiledResult.appName;
            msg.selfRender = compiledResult.selfRender;
        }
        post(msg);
    }

    function describe(err) {
        if (!err) {
            return 'Unknown error';
        }
        if (typeof err === 'string') {
            return err;
        }
        return (err.name ? err.name + ': ' : '') + (err.message || String(err));
    }

    function ensureBox() {
        var box = document.getElementById('simgen-error');
        if (!box && document.body) {
            box = document.createElement('div');
            box.id = 'simgen-error';
            box.setAttribute('role', 'alert');
            box.style.cssText = 'font:14px/1.4 system-ui,sans-serif;margin:12px;padding:12px 16px;border:1px solid #b02a37;'
                + 'border-radius:6px;background:#fbeaec;color:#58151c;';
            document.body.insertBefore(box, document.body.firstChild);
        }
        return box;
    }

    function button(label, onclick) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.addEventListener('click', onclick);
        return b;
    }

    /**
     * Shows an error and reports it to the Moodle page.
     *
     * @param {String} title
     * @param {*} err
     * @param {Boolean} fatal the simulation cannot continue (hides the app)
     * @param {String} extra additional details (e.g. React component stack)
     */
    function showError(title, err, fatal, extra) {
        var message = describe(err);
        var lines = err && err.stack ? String(err.stack).split('\n') : [];
        if (lines.length && message.indexOf(lines[0].trim()) !== -1) {
            // V8 repeats the message in the first line of the stack.
            lines.shift();
        }
        var stack = lines.slice(0, 8).join('\n') + (extra || '');
        if (!checkDone && !checkError) {
            checkError = message + (stack ? '\n\n' + stack : '');
        }

        // Report each error once (React development builds re-throw render errors), but always report fatal ones.
        var signature = message + '|' + !!fatal;
        if (reported < 5 && signature !== lastReported) {
            reported++;
            lastReported = signature;
            post({type: 'error', message: message, stack: stack, fatal: !!fatal});
        }
        if (fatalShown) {
            return;
        }
        var box = ensureBox();
        if (!box) {
            return;
        }
        if (fatal) {
            fatalShown = true;
            var root = document.getElementById('root');
            if (root) {
                root.hidden = true;
            }
            var loading = document.getElementById('simgen-loading');
            if (loading) {
                loading.hidden = true;
            }
        }
        box.hidden = false;
        box.innerHTML = '';

        var h = document.createElement('strong');
        h.textContent = title || STR.errortitle || 'Error';
        box.appendChild(h);

        var p = document.createElement('p');
        p.textContent = fatal ? (STR.errortext || '') : (STR.errortextnonfatal || '');
        box.appendChild(p);

        var details = document.createElement('details');
        var summary = document.createElement('summary');
        summary.textContent = STR.errordetails || 'Details';
        details.appendChild(summary);
        var pre = document.createElement('pre');
        pre.textContent = message + (stack ? '\n\n' + stack : '');
        details.appendChild(pre);
        box.appendChild(details);

        var actions = document.createElement('div');
        actions.className = 'simgen-error-actions';
        actions.appendChild(button(STR.reload || 'Reload', function() {
            post({type: 'reload'});
        }));
        if (savingEnabled) {
            actions.appendChild(button(STR.reset || 'Reset', function() {
                post({type: 'reset'});
            }));
        }
        if (!fatal) {
            actions.appendChild(button(STR.close || 'Close', function() {
                box.hidden = true;
            }));
        }
        box.appendChild(actions);
    }

    window.addEventListener('error', function(ev) {
        if (ev.target && ev.target !== window && ev.target.tagName === 'SCRIPT') {
            showError(STR.libsfailed, 'Could not load ' + (ev.target.src || 'script'), true);
            return;
        }
        showError(STR.errortitle, ev.error || ev.message, false);
    }, true);

    window.addEventListener('unhandledrejection', function(ev) {
        showError(STR.errortitle, ev.reason, false);
    });

    // ------------------------------------------------------------------------------------------------------------
    // Persistence.
    // ------------------------------------------------------------------------------------------------------------

    function flush() {
        if (saveTimer) {
            clearTimeout(saveTimer);
            saveTimer = null;
        }
        if (!savingEnabled) {
            return;
        }
        var json;
        try {
            json = JSON.stringify(store);
        } catch (e) {
            showError(STR.errortitle, e, false);
            return;
        }
        if (json === lastSent) {
            return;
        }
        lastSent = json;
        post({type: 'save', state: json});
    }

    function scheduleSave() {
        if (!savingEnabled) {
            return;
        }
        if (saveTimer) {
            clearTimeout(saveTimer);
        }
        saveTimer = setTimeout(flush, 600);
    }

    window.addEventListener('pagehide', flush);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            flush();
        }
    });

    var SimBridge = {
        /** Promise resolving with the saved state (object) or null as soon as the Moodle page answered. */
        ready: readyPromise,

        /** Whether progress is persisted in Moodle. */
        get savingEnabled() {
            return savingEnabled;
        },

        /** Information about the current user (first name only). */
        get user() {
            return (initData && initData.user) || {};
        },

        /** Whether the user completed the simulation before. */
        get completedBefore() {
            return !!(initData && initData.completed);
        },

        get: function(key, fallback) {
            return hasOwn.call(store, key) ? clone(store[key]) : fallback;
        },

        set: function(key, value) {
            store[key] = value;
            scheduleSave();
        },

        getAll: function() {
            return clone(store);
        },

        /** Merges an object into the saved state. */
        save: function(state) {
            if (state && typeof state === 'object') {
                Object.keys(state).forEach(function(k) {
                    store[k] = state[k];
                });
                scheduleSave();
            }
        },

        clear: function() {
            store = {};
            scheduleSave();
        },

        /**
         * Reports that the learner finished the simulation.
         *
         * @param {Object} result {score, maxScore, summary}
         */
        complete: function(result) {
            result = result || {};
            flush();
            var json = null;
            try {
                json = JSON.stringify(store);
            } catch (e) {
                json = null;
            }
            post({
                type: 'complete',
                state: savingEnabled ? json : null,
                score: toNumber(result.score),
                maxScore: toNumber(result.maxScore !== undefined ? result.maxScore : result.maxscore),
                summary: result.summary ? String(result.summary).slice(0, 4000) : ''
            });
        },

        /** Deletes the saved progress and restarts the simulation. */
        reset: function() {
            post({type: 'reset'});
        },

        /** Public data of the AI personas of this simulation: [{id, name, role, opening, maxTurns, signals}]. */
        personas: (CFG.personas || []).slice(),

        /**
         * Loads the stored conversation with a persona.
         *
         * @param {String} personaId
         * @returns {Promise} {messages, signals, turn, maxTurns, done}
         */
        loadConversation: function(personaId) {
            return request('conversation-load', {personaId: resolveId(personaId)});
        },

        /**
         * Sends a message to a persona (answer generated by the AI).
         *
         * @param {String} personaId
         * @param {String} message
         * @returns {Promise} {reply, messages, signals, turn, maxTurns, done}
         */
        converse: function(personaId, message) {
            return request('converse', {personaId: resolveId(personaId), message: String(message)});
        },

        /**
         * Deletes the conversation with a persona.
         *
         * @param {String} personaId
         * @returns {Promise} {messages, signals, turn, maxTurns, done}
         */
        resetConversation: function(personaId) {
            return request('conversation-reset', {personaId: resolveId(personaId)});
        },

        /**
         * Ends the conversation with a persona (no further messages, done becomes true).
         *
         * @param {String} personaId
         * @returns {Promise} {messages, signals, turn, maxTurns, ended, done}
         */
        endConversation: function(personaId) {
            return request('conversation-end', {personaId: resolveId(personaId)});
        }
    };

    /**
     * Normalises a persona id. Must stay identical to \mod_simgen\local\conversation::normalise_id().
     *
     * @param {String} id
     * @returns {String}
     */
    function normaliseId(id) {
        return String(id === undefined || id === null ? '' : id).toLowerCase().replace(/[^a-z0-9_-]/g, '').slice(0, 50);
    }

    // ------------------------------------------------------------------------------------------------------------
    // Requests to the Moodle page (live conversations).
    // ------------------------------------------------------------------------------------------------------------

    var pendingRequests = {};
    var requestCounter = 0;

    function request(type, payload) {
        if (!hasParent) {
            return Promise.reject(new Error(STR.conversationunavailable || 'Not available outside Moodle.'));
        }
        return new Promise(function(resolve, reject) {
            var id = ++requestCounter;
            var timer = setTimeout(function() {
                if (pendingRequests[id]) {
                    delete pendingRequests[id];
                    reject(new Error(STR.conversationtimeout || 'Timeout'));
                }
            }, 300000);
            pendingRequests[id] = {resolve: resolve, reject: reject, timer: timer};
            var msg = {type: type, requestId: id};
            Object.keys(payload).forEach(function(k) {
                msg[k] = payload[k];
            });
            post(msg);
        });
    }

    function handleResponse(d) {
        var entry = pendingRequests[d.requestId];
        if (!entry) {
            return;
        }
        delete pendingRequests[d.requestId];
        clearTimeout(entry.timer);
        if (d.ok) {
            entry.resolve(d.data);
        } else {
            entry.reject(new Error(d.error || 'Error'));
        }
    }

    /**
     * Finds a persona. Tolerates ids the generated code derived from the name ("Frau Keller") and falls back to
     * the only persona if there is just one - small models often do not use the exact id.
     *
     * @param {String} personaId
     * @returns {Object|null}
     */
    function findPersona(personaId) {
        var list = SimBridge.personas;
        var id = normaliseId(personaId);
        var i;
        for (i = 0; i < list.length; i++) {
            if (list[i].id === id) {
                return list[i];
            }
        }
        for (i = 0; i < list.length; i++) {
            if (normaliseId(list[i].name) === id) {
                return list[i];
            }
        }
        if (list.length === 1) {
            if (window.console) {
                window.console.warn('mod_simgen: unknown persona "' + personaId + '", using "' + list[0].id + '".');
            }
            return list[0];
        }
        return null;
    }

    function resolveId(personaId) {
        var persona = findPersona(personaId);
        return persona ? persona.id : normaliseId(personaId);
    }

    /**
     * Shows a message as Moodle notification on the page (independent of the generated user interface).
     *
     * @param {String} message
     */
    function notify(message) {
        post({type: 'notify', message: String(message || '').slice(0, 1000)});
    }

    function unknownPersonaMessage(personaId) {
        var ids = SimBridge.personas.map(function(p) {
            return p.id;
        }).join(', ');
        return (STR.unknownpersona || 'Unknown persona') + ': "' + personaId + '"' + (ids ? ' (' + ids + ')' : '');
    }

    /**
     * React hook for a live conversation with an AI persona.
     *
     * @param {String} personaId
     * @returns {Object} {persona, messages, send, pending, error, signals, turn, maxTurns, done, ready, reset}
     */
    function useConversation(personaId) {
        var R = window.React;
        var requestedId = String(personaId === undefined || personaId === null ? '' : personaId);
        var persona = findPersona(requestedId);
        personaId = persona ? persona.id : normaliseId(requestedId);
        var initialMessages = persona && persona.opening ? [{role: 'persona', text: persona.opening}] : [];

        var dataState = R.useState({
            messages: initialMessages,
            signals: {},
            turn: 0,
            maxTurns: persona ? persona.maxTurns : 0,
            done: false
        });
        var data = dataState[0];
        var setData = dataState[1];
        var readyState = R.useState(false);
        var pendingState = R.useState(false);
        var errorState = R.useState(persona ? null : unknownPersonaMessage(requestedId));
        var pendingRef = R.useRef(false);

        R.useEffect(function() {
            var alive = true;
            if (!persona) {
                // Visible for everybody and reported to the teacher (who can let the AI fix the code).
                showError(STR.errortitle, new Error(unknownPersonaMessage(requestedId)), false);
                readyState[1](true);
                return undefined;
            }
            SimBridge.loadConversation(personaId).then(function(result) {
                if (alive) {
                    setData(result);
                    readyState[1](true);
                }
            }, function(err) {
                if (alive) {
                    errorState[1](err.message);
                    readyState[1](true);
                }
            });
            return function() {
                alive = false;
            };
        }, [personaId]);

        var send = R.useCallback(function(text) {
            text = String(text === undefined || text === null ? '' : text).trim();
            if (!text || pendingRef.current) {
                return Promise.resolve(null);
            }
            if (!persona) {
                // Never swallow a message silently.
                errorState[1](unknownPersonaMessage(requestedId));
                notify(unknownPersonaMessage(requestedId));
                return Promise.resolve(null);
            }
            pendingRef.current = true;
            pendingState[1](true);
            errorState[1](null);
            // Show the learner's message immediately.
            setData(function(prev) {
                return Object.assign({}, prev, {messages: prev.messages.concat([{role: 'user', text: text}])});
            });
            return SimBridge.converse(personaId, text).then(function(result) {
                pendingRef.current = false;
                pendingState[1](false);
                setData(result);
                return result;
            }, function(err) {
                pendingRef.current = false;
                pendingState[1](false);
                errorState[1](err.message);
                // Keep the message visible (marked as failed); the Moodle page shows the error as notification.
                setData(function(prev) {
                    var messages = prev.messages.slice();
                    if (messages.length && messages[messages.length - 1].role === 'user') {
                        messages[messages.length - 1] = Object.assign({}, messages[messages.length - 1], {failed: true});
                    }
                    return Object.assign({}, prev, {messages: messages});
                });
                return null;
            });
        }, [personaId, requestedId]);

        var reset = R.useCallback(function() {
            if (!persona) {
                return Promise.resolve(null);
            }
            errorState[1](null);
            return SimBridge.resetConversation(personaId).then(function(result) {
                setData(result);
                return result;
            }, function(err) {
                errorState[1](err.message);
                return null;
            });
        }, [personaId]);

        var end = R.useCallback(function() {
            if (!persona || pendingRef.current) {
                return Promise.resolve(null);
            }
            errorState[1](null);
            return SimBridge.endConversation(personaId).then(function(result) {
                setData(result);
                return result;
            }, function(err) {
                errorState[1](err.message);
                return null;
            });
        }, [personaId]);

        return {
            persona: persona ? {id: persona.id, name: persona.name, role: persona.role} : {id: personaId, name: personaId, role: ''},
            messages: data.messages || [],
            signals: data.signals || {},
            turn: data.turn || 0,
            maxTurns: data.maxTurns || 0,
            ended: !!data.ended,
            done: !!data.done,
            ready: readyState[0],
            pending: pendingState[0],
            error: errorState[0],
            send: send,
            end: end,
            reset: reset
        };
    }

    /**
     * Persisted variant of React.useState.
     *
     * @param {String} key unique key
     * @param {*} initialValue initial value or initialiser function
     * @returns {Array} [value, setValue]
     */
    function useSimState(key, initialValue) {
        var R = window.React;
        var pair = R.useState(function() {
            if (hasOwn.call(store, key)) {
                return store[key];
            }
            return typeof initialValue === 'function' ? initialValue() : initialValue;
        });
        var value = pair[0];
        R.useEffect(function() {
            store[key] = value;
            scheduleSave();
        }, [value]);
        return pair;
    }

    // ------------------------------------------------------------------------------------------------------------
    // Math building blocks: <Formula> (KaTeX) and <MathBoard> (JSXGraph). The libraries are bundled with the plugin
    // and only loaded if the generated code uses these names (\mod_simgen\local\html_builder::MATH_LIBRARIES).
    // ------------------------------------------------------------------------------------------------------------

    var TEX_COMMANDS = ['alpha', 'beta', 'gamma', 'delta', 'epsilon', 'varepsilon', 'zeta', 'eta', 'theta', 'vartheta',
        'iota', 'kappa', 'lambda', 'mu', 'nu', 'xi', 'pi', 'rho', 'sigma', 'tau', 'upsilon', 'phi', 'varphi', 'chi', 'psi',
        'omega', 'Gamma', 'Delta', 'Theta', 'Lambda', 'Xi', 'Pi', 'Sigma', 'Phi', 'Psi', 'Omega', 'cdot', 'times', 'div',
        'pm', 'mp', 'leq', 'geq', 'neq', 'approx', 'infty', 'sqrt', 'frac', 'dfrac', 'tfrac', 'sum', 'int', 'prod', 'lim',
        'sin', 'cos', 'tan', 'ln', 'log', 'exp', 'text', 'mathrm', 'left', 'right', 'partial', 'nabla', 'Rightarrow',
        'rightarrow', 'quad', 'vec', 'overline', 'degree'];
    // Not after a control character: "\beta" became "\x08eta", which is repaired below.
    var TEX_WORDS = new RegExp('(^|[^A-Za-z\\f\\x08\\x0B\\t\\r\\n])(' + TEX_COMMANDS.join('|') + ')(?![A-Za-z])', 'g');

    /**
     * Repairs LaTeX that went through a JavaScript string with single backslashes: "\frac" contains a form feed,
     * "\sigma" becomes "sigma". Literals in tex attributes are already repaired when compiling (texPlugin); this
     * covers values computed at runtime.
     *
     * @param {String} tex
     * @returns {String}
     */
    function repairTex(tex) {
        tex = String(tex);
        if (tex.indexOf('\\') === -1) {
            // All backslashes were lost.
            tex = tex.replace(TEX_WORDS, '$1\\$2');
        }
        return tex
            .replace(/\f/g, '\\f')
            .replace(/\x08/g, '\\b')
            .replace(/\x0B/g, '\\v')
            .replace(/\t(?=[a-z])/g, '\\t')
            .replace(/\r(?=[a-z])/g, '\\r')
            .replace(/\n(?=(u|eq|e|abla|ot|eg|ewline|leq|geq|mid|i|ni|parallel|subseteq|exists)(?![A-Za-z]))/g, '\\n');
    }

    /**
     * Content of a string literal as the author meant it for LaTeX: "\frac" and "\\frac" both become \frac.
     *
     * @param {String} raw source text between the quotes
     * @returns {String}
     */
    function texFromRaw(raw) {
        return raw.replace(/\\u([0-9a-fA-F]{4})|\\([nrt])(?![A-Za-z])|\\\r?\n|\\([\\'"`])/g, function(m, hex, ctrl, quote) {
            if (hex) {
                return String.fromCharCode(parseInt(hex, 16));
            }
            if (ctrl) {
                return ctrl === 'n' ? '\n' : (ctrl === 't' ? '\t' : '');
            }
            return quote || '';
        });
    }

    var TEX_KEYS = /^(tex|latex|formula|formel)$/i;

    /**
     * Babel plugin: repairs single backslashes in string literals that hold LaTeX (attribute tex={…} and object
     * properties named tex/latex/formula). Plain JSX attributes (tex="\frac…") keep backslashes anyway.
     *
     * @param {Object} babel
     * @returns {Object}
     */
    function texPlugin(babel) {
        var t = babel.types;

        function fixString(path) {
            var node = path.node;
            if (node.simgenTex || !node.extra || typeof node.extra.raw !== 'string') {
                return;
            }
            var raw = node.extra.raw.slice(1, -1);
            if (raw.indexOf('\\') === -1) {
                return;
            }
            var literal = t.stringLiteral(texFromRaw(raw));
            literal.simgenTex = true;
            path.replaceWith(literal);
        }

        function fixTemplate(path) {
            if (path.parentPath.isTaggedTemplateExpression()) {
                // String.raw`…` is already correct.
                return;
            }
            path.node.quasis.forEach(function(quasi) {
                if (quasi.simgenTex || quasi.value.raw.indexOf('\\') === -1) {
                    return;
                }
                var cooked = texFromRaw(quasi.value.raw);
                quasi.value = {
                    raw: cooked.replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$\{/g, '\\${'),
                    cooked: cooked
                };
                quasi.simgenTex = true;
            });
        }

        function fixExpression(path) {
            if (!path || !path.node) {
                return;
            }
            if (path.isStringLiteral()) {
                fixString(path);
            } else if (path.isTemplateLiteral()) {
                fixTemplate(path);
            } else {
                path.traverse({
                    StringLiteral: fixString,
                    TemplateLiteral: fixTemplate
                });
            }
        }

        return {
            visitor: {
                JSXAttribute: function(path) {
                    var name = path.node.name && path.node.name.name;
                    if (typeof name === 'string' && TEX_KEYS.test(name) && path.get('value').isJSXExpressionContainer()) {
                        fixExpression(path.get('value.expression'));
                    }
                },
                ObjectProperty: function(path) {
                    var key = path.node.key;
                    var name = t.isIdentifier(key) ? key.name : (t.isStringLiteral(key) ? key.value : null);
                    if (!path.node.computed && name && TEX_KEYS.test(name)) {
                        fixExpression(path.get('value'));
                    }
                }
            }
        };
    }

    var reportedTex = {};

    /**
     * Renders LaTeX with KaTeX.
     *
     * @param {String} tex
     * @param {Boolean} display
     * @returns {Object} {html, error} (html is null if KaTeX is not available)
     */
    function renderTex(tex, display) {
        var K = window.katex;
        if (!K) {
            return {html: null, error: null};
        }
        var options = {displayMode: display, throwOnError: true, strict: 'ignore', trust: false};
        try {
            return {html: K.renderToString(tex, options), error: null};
        } catch (e) {
            options.throwOnError = false;
            var html = null;
            try {
                html = K.renderToString(tex, options);
            } catch (e2) {
                html = null;
            }
            return {html: html, error: new Error('<Formula tex="' + tex + '">: ' + describe(e))};
        }
    }

    /**
     * Formula (LaTeX): <Formula tex="\frac{a}{b}" /> inline, <Formula display tex="…" /> as a block.
     *
     * @param {Object} props {tex, display, block, className, style}
     * @returns {Object} React element
     */
    function Formula(props) {
        var R = window.React;
        var tex = props.tex !== undefined && props.tex !== null ? props.tex : props.children;
        tex = repairTex(Array.isArray(tex) ? tex.join('') : (tex === undefined || tex === null ? '' : tex));
        var display = !!(props.display || props.block);
        var result = R.useMemo(function() {
            return renderTex(tex, display);
        }, [tex, display]);

        R.useEffect(function() {
            if (result.error && !reportedTex[tex]) {
                // Report once (visible to the teacher, who can let the AI fix it).
                reportedTex[tex] = true;
                showError(STR.errortitle, result.error, false);
            }
        }, [result]);

        var attributes = {
            className: 'simgen-formula' + (display ? ' simgen-formula-display' : '') + (props.className ? ' ' + props.className : ''),
            style: props.style
        };
        if (result.html === null) {
            return R.createElement(display ? 'div' : 'span', attributes, R.createElement('code', null, tex));
        }
        attributes.dangerouslySetInnerHTML = {__html: result.html};
        return R.createElement(display ? 'div' : 'span', attributes);
    }

    var boardCounter = 0;

    /**
     * Interactive JSXGraph board.
     *
     * - setup(board): called once (draggable points, static elements); its return value is passed to draw.
     * - draw(board, handles): called after mounting and whenever deps change (every render without deps); the
     *   elements created by the previous call are removed first.
     *
     * @param {Object} props {boundingBox, axis, grid, keepAspectRatio, navigation, height, aspectRatio, title,
     *     description, attributes, setup, draw, deps, className, style}
     * @returns {Object} React element
     */
    function MathBoard(props) {
        var R = window.React;
        var idRef = R.useRef(null);
        if (idRef.current === null) {
            idRef.current = 'simgen-board-' + (++boardCounter);
        }
        var boardRef = R.useRef(null);
        var handlesRef = R.useRef(undefined);
        var drawnRef = R.useRef([]);
        var propsRef = R.useRef(props);
        propsRef.current = props;

        R.useEffect(function() {
            var JXG = window.JXG;
            if (!JXG) {
                showError(STR.libsfailed, new Error('JSXGraph is not available.'), false);
                return undefined;
            }
            var p = propsRef.current;
            var attributes = {
                boundingbox: p.boundingBox || p.boundingbox || [-5, 5, 5, -5],
                axis: p.axis === undefined ? true : !!p.axis,
                grid: !!p.grid,
                keepAspectRatio: !!p.keepAspectRatio,
                showCopyright: false,
                showNavigation: !!p.navigation,
                title: p.title || '',
                description: p.description || ''
            };
            Object.keys(p.attributes || {}).forEach(function(k) {
                attributes[k] = p.attributes[k];
            });
            var board;
            try {
                board = JXG.JSXGraph.initBoard(idRef.current, attributes);
            } catch (e) {
                showError(STR.errortitle, e, false);
                return undefined;
            }
            boardRef.current = board;
            if (typeof p.setup === 'function') {
                board.suspendUpdate();
                try {
                    handlesRef.current = p.setup(board);
                } catch (e) {
                    showError(STR.errortitle, e, false);
                } finally {
                    board.unsuspendUpdate();
                }
            }
            return function() {
                boardRef.current = null;
                drawnRef.current = [];
                handlesRef.current = undefined;
                try {
                    JXG.JSXGraph.freeBoard(board);
                } catch (e) {
                    // Already gone.
                }
            };
        }, []);

        R.useEffect(function() {
            var board = boardRef.current;
            var p = propsRef.current;
            var draw = typeof p.draw === 'function' ? p.draw : (typeof p.children === 'function' ? p.children : null);
            if (!board || !draw) {
                return;
            }
            board.suspendUpdate();
            try {
                drawnRef.current.slice().reverse().forEach(function(element) {
                    if (board.objects[element.id]) {
                        board.removeObject(element);
                    }
                });
                drawnRef.current = [];
                var before = {};
                Object.keys(board.objects).forEach(function(id) {
                    before[id] = true;
                });
                try {
                    draw(board, handlesRef.current);
                } finally {
                    drawnRef.current = Object.keys(board.objects).filter(function(id) {
                        return !before[id];
                    }).map(function(id) {
                        return board.objects[id];
                    });
                }
            } catch (e) {
                showError(STR.errortitle, e, false);
            } finally {
                board.unsuspendUpdate();
            }
        }, Array.isArray(props.deps) ? props.deps : undefined);

        var style = {width: '100%', height: typeof props.height === 'number' ? props.height + 'px' : (props.height || '360px')};
        if (props.aspectRatio) {
            style.height = 'auto';
            style.aspectRatio = String(props.aspectRatio);
        }
        Object.keys(props.style || {}).forEach(function(k) {
            style[k] = props.style[k];
        });
        return R.createElement('div', {
            id: idRef.current,
            className: 'jxgbox simgen-board' + (props.className ? ' ' + props.className : ''),
            style: style
        });
    }

    window.SimBridge = SimBridge;
    window.useSimState = useSimState;
    window.useConversation = useConversation;
    window.Formula = Formula;
    window.MathBoard = MathBoard;

    // ------------------------------------------------------------------------------------------------------------
    // Handshake with the Moodle page.
    // ------------------------------------------------------------------------------------------------------------

    function finishInit(data) {
        if (initData) {
            return;
        }
        initData = data || {};
        savingEnabled = !!initData.savestate;
        if (initData.state) {
            try {
                var parsed = JSON.parse(initData.state);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                    store = parsed;
                    lastSent = initData.state;
                }
            } catch (e) {
                // Corrupt state - start fresh.
            }
        }
        resolveReady(Object.keys(store).length ? clone(store) : null);
    }

    window.addEventListener('message', function(ev) {
        if (!hasParent || ev.source !== window.parent) {
            return;
        }
        if (CFG.parentOrigin && ev.origin !== CFG.parentOrigin) {
            return;
        }
        var d = ev.data;
        if (!d || d.source !== 'simgen-host') {
            return;
        }
        if (d.type === 'response') {
            handleResponse(d);
        } else if (d.type === 'init') {
            finishInit(d);
        } else if (d.type === 'flush') {
            flush();
        }
    });

    var helloTries = 0;
    function hello() {
        if (initData) {
            return;
        }
        if (!hasParent || helloTries >= 40) {
            // Standalone or no answer within 10 seconds: run without persistence.
            finishInit({});
            return;
        }
        helloTries++;
        post({type: 'hello'});
        setTimeout(hello, 250);
    }
    hello();

    // ------------------------------------------------------------------------------------------------------------
    // Height reporting.
    // ------------------------------------------------------------------------------------------------------------

    var lastHeight = 0;
    var growths = 0;
    var growthReset = null;

    function reportHeight() {
        if (!document.body) {
            return;
        }
        var style = window.getComputedStyle(document.body);
        var height = Math.ceil(document.body.getBoundingClientRect().height
            + (parseFloat(style.marginTop) || 0) + (parseFloat(style.marginBottom) || 0));
        if (Math.abs(height - lastHeight) < 3) {
            return;
        }
        if (height > lastHeight) {
            // Guard against endless growth loops (e.g. content sized with 100vh).
            growths++;
            clearTimeout(growthReset);
            growthReset = setTimeout(function() {
                growths = 0;
            }, 1500);
            if (growths > 15) {
                return;
            }
        }
        lastHeight = height;
        post({type: 'resize', height: height});
    }

    function startResize() {
        reportHeight();
        // React renders asynchronously; measure again shortly after mounting.
        [50, 300, 1000].forEach(function(delay) {
            setTimeout(reportHeight, delay);
        });
        if (window.ResizeObserver) {
            new ResizeObserver(reportHeight).observe(document.body);
        }
        // Fallback: ResizeObserver does not fire while the page is not rendered (e.g. background tab).
        setInterval(reportHeight, 2000);
    }

    // ------------------------------------------------------------------------------------------------------------
    // React: compile and mount.
    // ------------------------------------------------------------------------------------------------------------

    var GLOBAL_HOOKS = ['useState', 'useEffect', 'useLayoutEffect', 'useRef', 'useMemo', 'useCallback', 'useReducer',
        'useContext', 'createContext', 'useId', 'Fragment', 'memo', 'forwardRef'];

    /**
     * Removes module syntax that cannot run in a classic script.
     *
     * @param {String} src
     * @returns {String}
     */
    function preprocess(src) {
        return src
            .replace(/^\s*import\s+[\s\S]*?\s+from\s+['"][^'"]+['"]\s*;?[ \t]*$/gm, '')
            .replace(/^\s*import\s+['"][^'"]+['"]\s*;?[ \t]*$/gm, '')
            .replace(/^(\s*)export\s+default\s+(function|class)\b/gm, '$1$2')
            .replace(/^(\s*)export\s+default\s+(?=\(|async\b)/gm, '$1const App = ')
            .replace(/^\s*export\s+default\s+[A-Za-z_$][\w$]*\s*;?[ \t]*$/gm, '')
            .replace(/^(\s*)export\s+(?=(const|let|var|function|class)\b)/gm, '$1');
    }

    function transform(src) {
        var B = window.Babel;
        try {
            return B.transform(src, {
                presets: [['react', {runtime: 'classic'}]],
                plugins: [texPlugin],
                filename: 'simulation.jsx',
                sourceType: 'script'
            }).code;
        } catch (e) {
            // Retry with TypeScript support (models sometimes add type annotations).
            try {
                return B.transform(src, {
                    presets: [['typescript', {isTSX: true, allExtensions: true}], ['react', {runtime: 'classic'}]],
                    plugins: [texPlugin],
                    filename: 'simulation.tsx',
                    sourceType: 'script'
                }).code;
            } catch (e2) {
                throw e;
            }
        }
    }

    function makeErrorBoundary(R) {
        function ErrorBoundary(props) {
            R.Component.call(this, props);
            this.state = {error: null};
        }
        ErrorBoundary.prototype = Object.create(R.Component.prototype);
        ErrorBoundary.prototype.constructor = ErrorBoundary;
        ErrorBoundary.getDerivedStateFromError = function(error) {
            return {error: error};
        };
        ErrorBoundary.prototype.componentDidCatch = function(error, info) {
            showError(STR.errortitle, error, true, info && info.componentStack ? '\n' + info.componentStack : '');
        };
        ErrorBoundary.prototype.render = function() {
            return this.state.error ? null : this.props.children;
        };
        return ErrorBoundary;
    }

    function mountReact() {
        // Published versions that passed the check come precompiled (no Babel needed).
        var precompiled = typeof CFG.compiled === 'string' && CFG.compiled !== '';
        if (typeof window.React === 'undefined' || typeof window.ReactDOM === 'undefined'
                || (!precompiled && typeof window.Babel === 'undefined')) {
            // A network problem says nothing about the code: no check result.
            checkDone = true;
            showError(STR.libsfailed, new Error('React, ReactDOM or Babel could not be loaded.'), true);
            return;
        }
        var R = window.React;
        GLOBAL_HOOKS.forEach(function(name) {
            if (!(name in window) && R[name]) {
                window[name] = R[name];
            }
        });

        var compiled;
        var appName = 'App';
        var selfRender = false;
        if (precompiled) {
            compiled = CFG.compiled;
            appName = CFG.appName || 'App';
            selfRender = !!CFG.selfRender;
        } else {
            var src = String(CFG.source || '');
            var m = src.match(/export\s+default\s+(?:function\s+|class\s+)?([A-Za-z_$][\w$]*)/);
            if (m && m[1] !== 'function' && m[1] !== 'class' && m[1] !== 'async') {
                appName = m[1];
            }
            src = preprocess(src);
            selfRender = /\bReactDOM\s*\.\s*(createRoot|render)\s*\(|(^|[^.\w])createRoot\s*\(/.test(src);

            try {
                compiled = transform(src);
            } catch (e) {
                showError(STR.compileerror, e, true);
                return;
            }
            compiledResult = {code: compiled, appName: appName, selfRender: selfRender};
        }

        window.__simgenApp = null;
        var script = document.createElement('script');
        script.text = compiled + '\n;window.__simgenApp = (typeof ' + appName + ' !== "undefined") ? ' + appName
            + ' : ((typeof App !== "undefined") ? App : null);';
        document.body.appendChild(script);

        if (fatalShown || selfRender) {
            return;
        }
        var App = window.__simgenApp;
        if (!App) {
            showError(STR.noapp, new Error('No component "App" was defined.'), true);
            return;
        }
        var ErrorBoundary = makeErrorBoundary(R);
        window.ReactDOM.createRoot(document.getElementById('root'))
            .render(R.createElement(ErrorBoundary, null, R.createElement(App)));
    }

    /**
     * Called at the end of <body> of React documents.
     */
    window.__simgenStart = function() {
        if (CFG.css) {
            var style = document.createElement('style');
            style.id = 'simgen-css';
            style.textContent = CFG.css;
            document.head.appendChild(style);
        }
        readyPromise.then(function() {
            var loading = document.getElementById('simgen-loading');
            if (loading) {
                loading.hidden = true;
            }
            try {
                mountReact();
            } catch (e) {
                showError(STR.errortitle, e, true);
            }
            startResize();
            setTimeout(concludeCheck, CHECK_DELAY);
        });
    };

    if (CFG.codetype !== 'react') {
        // Complete HTML documents run themselves; only start the height reporting.
        var startHtml = function() {
            startResize();
            setTimeout(concludeCheck, CHECK_DELAY);
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startHtml);
        } else {
            startHtml();
        }
    }
})();
