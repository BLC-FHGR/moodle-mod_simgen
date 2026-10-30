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

use moodle_exception;

/**
 * Extracts the code blocks from the AI response.
 *
 * local_ai_manager converts the Markdown answer of most purposes (chat, singleprompt) into sanitised HTML, so code
 * blocks arrive as <pre class="language-jsx"><code>…escaped code…</code></pre>. Raw Markdown fences (purposes that
 * return the plain answer) are supported as well.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_parser {

    /** @var string[] languages treated as React/JS code */
    const JS_LANGUAGES = ['jsx', 'js', 'javascript', 'tsx', 'ts', 'typescript', 'react'];

    /**
     * Parses the AI response.
     *
     * @param string $content response content of local_ai_manager
     * @return array ['codetype' => 'react'|'html', 'sourcecode' => string, 'css' => string, 'personas' => string,
     *               'timeplan' => string] personas is the raw JSON of the persona definitions ('' if none),
     *               timeplan the normalised JSON of the schedule ('' if none)
     * @throws moodle_exception if no usable code was found
     */
    public static function parse(string $content): array {
        $blocks = self::extract_blocks($content);

        $css = [];
        $js = [];
        $html = [];
        $json = [];
        $unknown = [];
        foreach ($blocks as $block) {
            $lang = $block['lang'];
            if ($lang === 'css') {
                $css[] = $block['code'];
            } else if (in_array($lang, self::JS_LANGUAGES, true)) {
                $js[] = $block['code'];
            } else if ($lang === 'html') {
                $html[] = $block['code'];
            } else if ($lang === 'json') {
                $json[] = $block['code'];
            } else {
                $unknown[] = $block['code'];
            }
        }

        // Unlabelled blocks: guess by content.
        foreach ($unknown as $code) {
            if (preg_match('/^\s*[\[{]/', $code)
                    && (strpos($code, '"personas"') !== false || strpos($code, '"plan"') !== false)) {
                $json[] = $code;
            } else if (preg_match('/function\s+App\b|const\s+App\s*=|React\./', $code)) {
                $js[] = $code;
            } else if (stripos($code, '<html') !== false || stripos($code, '<!doctype') !== false) {
                $html[] = $code;
            } else if (preg_match('/^\s*[.#:a-z][^{]*\{[^}]*:[^}]*\}/mi', $code)) {
                $css[] = $code;
            }
        }

        // The schedule is the JSON block with "plan" (and without "personas").
        $timeplan = '';
        foreach ($json as $i => $block) {
            if (strpos($block, '"personas"') === false && ($plan = time_plan::normalise($block))) {
                $timeplan = time_plan::to_json($plan);
                unset($json[$i]);
                break;
            }
        }

        if ($js) {
            $source = trim(implode("\n\n", $js));
            if (!preg_match('/\bApp\b/', $source) && !preg_match('/export\s+default/', $source)) {
                throw new moodle_exception('error_noapp', 'mod_simgen');
            }
            // Persona definitions: prefer the block that contains "personas".
            $personas = '';
            foreach ($json as $block) {
                if ($personas === '' || strpos($block, '"personas"') !== false) {
                    $personas = trim($block);
                }
            }
            return [
                'codetype' => 'react',
                'sourcecode' => $source,
                'css' => trim(implode("\n\n", $css)),
                'personas' => $personas,
                'timeplan' => $timeplan,
            ];
        }
        if ($html) {
            // Take the longest document.
            usort($html, fn($a, $b) => strlen($b) <=> strlen($a));
            return [
                'codetype' => 'html',
                'sourcecode' => trim($html[0]),
                'css' => '',
                'personas' => '',
                'timeplan' => $timeplan,
            ];
        }
        throw new moodle_exception('error_nocode', 'mod_simgen');
    }

    /**
     * Parses the AI response of a facilitator guide.
     *
     * The guide is requested as one Markdown code block, because local_ai_manager converts the answer to HTML and
     * the code block keeps the Markdown source intact. Without a block, the answer itself is used (Markdown or the
     * HTML of local_ai_manager; both are rendered with FORMAT_MARKDOWN, which keeps HTML).
     *
     * @param string $content response content of local_ai_manager
     * @return string guide in Markdown
     * @throws moodle_exception if the response is empty
     */
    public static function parse_guide(string $content): string {
        $guide = '';
        foreach (self::extract_blocks($content) as $block) {
            if (in_array($block['lang'], ['markdown', 'md', ''], true)
                    && \core_text::strlen($block['code']) > \core_text::strlen($guide)) {
                $guide = $block['code'];
            }
        }
        // A short block is probably an example inside an unfenced answer, not the guide.
        $plain = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (\core_text::strlen(trim($guide)) < \core_text::strlen(trim($plain)) / 2) {
            $guide = $content;
        }
        $guide = trim($guide);
        if ($guide === '') {
            throw new moodle_exception('error_noguide', 'mod_simgen');
        }
        return $guide;
    }

    /**
     * Finds all code blocks.
     *
     * @param string $content
     * @return array list of ['lang' => string, 'code' => string]
     */
    public static function extract_blocks(string $content): array {
        $blocks = [];

        // 1. HTML produced by local_ai_manager's Markdown conversion.
        if (stripos($content, '<pre') !== false && stripos($content, '<code') !== false) {
            $pattern = '#<pre([^>]*)>\s*<code([^>]*)>(.*?)</code>\s*</pre>#is';
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $lang = self::language_from_attributes($match[1] . ' ' . $match[2]);
                    $code = html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $blocks[] = ['lang' => $lang, 'code' => $code];
                }
            }
            if ($blocks) {
                return $blocks;
            }
        }

        // 2. Raw Markdown fences. The block may be empty and the closing fence may be longer than the opening one.
        $text = str_replace(["\r\n", "\r"], "\n", $content);
        if (preg_match_all('/^[ \t]*(`{3,}|~{3,})[ \t]*([A-Za-z0-9_+#.-]*)[^\n]*\n(.*?)^[ \t]*\1[`~]*[ \t]*$/ms', $text,
                $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $code = substr($match[3], -1) === "\n" ? substr($match[3], 0, -1) : $match[3];
                $blocks[] = ['lang' => strtolower($match[2]), 'code' => $code];
            }
        }

        // 3. Truncated answer: an opening fence without closing fence (e.g. token limit reached).
        if (!$blocks && preg_match('/^[ \t]*```[ \t]*([A-Za-z0-9_+#.-]*)[^\n]*\n(.*)$/ms', $text, $match)) {
            $blocks[] = ['lang' => strtolower($match[1]), 'code' => $match[2]];
        }
        return $blocks;
    }

    /**
     * Reads the language from class attributes like class="language-jsx".
     *
     * @param string $attributes
     * @return string
     */
    private static function language_from_attributes(string $attributes): string {
        if (preg_match('/language-([A-Za-z0-9_+#.-]+)/', $attributes, $m)) {
            $lang = strtolower($m[1]);
            return $lang === 'none' ? '' : $lang;
        }
        if (preg_match('/lang(?:uage)?="?([A-Za-z0-9_+#.-]+)/', $attributes, $m)) {
            return strtolower($m[1]);
        }
        return '';
    }
}
