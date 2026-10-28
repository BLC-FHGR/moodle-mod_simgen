<?php
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

namespace mod_simgen\local;

use stdClass;

/**
 * Static checks of generated code (before teachers see it).
 *
 * The checks only look for problems that certainly break the simulation in the sandbox or contradict the runtime
 * contract. Errors trigger an automatic repair round (see generator::autorepair_allowed()), warnings are only shown.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class code_checker {

    /** @var string the simulation does not work */
    const ERROR = 'error';
    /** @var string worth knowing, but no reason for a repair round */
    const WARNING = 'warning';

    /**
     * Checks a version (or a parsed AI response with the same fields).
     *
     * @param stdClass $simgen instance record
     * @param stdClass $version with codetype, sourcecode, css and personas (JSON or null)
     * @return array list of ['severity' => ERROR|WARNING, 'message' => string]
     */
    public static function check(stdClass $simgen, stdClass $version): array {
        $source = (string)$version->sourcecode;
        $code = self::strip_comments($source);
        $all = $code . "\n" . (string)($version->css ?? '');
        $issues = [];
        $add = function(string $severity, string $key, $a = null) use (&$issues) {
            $issues[] = ['severity' => $severity, 'message' => get_string('check_' . $key, 'mod_simgen', $a)];
        };

        if (trim($source) === '') {
            $add(self::ERROR, 'empty');
            return $issues;
        }

        if ($version->codetype === 'react') {
            $selfrender = preg_match('/\bReactDOM\s*\.\s*(createRoot|render)\s*\(|(^|[^.\w])createRoot\s*\(/', $code);
            if (!$selfrender && !preg_match('/\b(function|class)\s+App\b|\b(const|let|var)\s+App\s*=|\bexport\s+default\b/',
                    $code)) {
                $add(self::ERROR, 'noapp');
            }
            // Code cut off by the output limit of the model ends in the middle of an expression.
            $end = preg_replace('/\bexport\s+default\s+[A-Za-z_$][\w$]*\s*;?\s*$/', '}', $code);
            if (!preg_match('/[};)\]>]\s*$/', $end)) {
                $add(self::ERROR, 'truncated');
            }
        } else if (stripos($source, '<html') !== false && stripos($source, '</html>') === false) {
            $add(self::ERROR, 'truncated');
        }

        // Not available in the sandbox (opaque origin, connect-src 'none').
        if (preg_match('/\b(localStorage|sessionStorage|indexedDB|document\.cookie)\b/', $code, $m)) {
            $add(self::ERROR, 'storage', $m[1]);
        }
        if (preg_match('/\bfetch\s*\(|\b(XMLHttpRequest|WebSocket|EventSource)\b|sendBeacon\s*\(/', $code, $m)) {
            $add(self::ERROR, 'network', trim($m[0], " \t("));
        }
        // External resources are blocked by the CSP (links to websites are fine).
        if (preg_match('~\bsrc\s*=\s*\{?\s*["\'`](https?:)?//|<link\b[^>]*\bhref\s*=\s*["\'](https?:)?//'
                . '|url\(\s*["\']?(https?:)?//|@import\s+(url\()?\s*["\']?(https?:)?//~i', $all)) {
            $add(self::ERROR, 'external');
        }

        // useSimState keys must be unique (otherwise two states overwrite each other).
        if (preg_match_all('/\buseSimState\s*\(\s*["\'`]([^"\'`]+)["\'`]/', $code, $m)) {
            $duplicates = array_keys(array_filter(array_count_values($m[1]), fn($n) => $n > 1));
            if ($duplicates) {
                $add(self::ERROR, 'duplicatekeys', implode(', ', $duplicates));
            }
        }

        // Live conversations: every persona the code talks to must be defined.
        if (preg_match_all('/\buseConversation\s*\(\s*["\'`]([^"\'`]+)["\'`]/', $code, $m)) {
            $known = array_keys(conversation::from_version($version));
            $unknown = array_diff(array_unique(array_map([conversation::class, 'normalise_id'], $m[1])), $known);
            if ($unknown) {
                $add(self::ERROR, 'unknownpersona', implode(', ', $unknown));
            }
        }

        // Completion: without SimBridge.complete() the activity can never be completed.
        if (strpos($code, 'SimBridge.complete') === false) {
            $add(!empty($simgen->completionfinished) ? self::ERROR : self::WARNING, 'nocomplete');
        }

        return $issues;
    }

    /**
     * Only the errors of a list of issues.
     *
     * @param array $issues result of check()
     * @return array
     */
    public static function errors(array $issues): array {
        return array_values(array_filter($issues, fn($issue) => $issue['severity'] === self::ERROR));
    }

    /**
     * Change request for the AI from a list of issues.
     *
     * @param array $issues
     * @return string
     */
    public static function repair_request(array $issues): string {
        $lines = array_map(fn($issue) => '- ' . $issue['message'], $issues);
        return get_string('autorepair_static', 'mod_simgen', implode("\n", $lines));
    }

    /**
     * Removes JavaScript comments (roughly: line comments only if they start a line or follow whitespace,
     * so URLs like https://… in strings are kept).
     *
     * @param string $code
     * @return string
     */
    private static function strip_comments(string $code): string {
        $code = preg_replace('~/\*.*?\*/~s', '', $code);
        $code = preg_replace('~(^|[ \t;{}])//[^\n]*~m', '$1', $code);
        return rtrim($code);
    }
}
