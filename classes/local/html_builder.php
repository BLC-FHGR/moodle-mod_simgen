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
 * Builds the HTML document of a simulation (served by sim.php inside a sandboxed iframe).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_builder {

    /** @var string default React UMD build */
    const DEFAULT_REACT = 'https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js';
    /** @var string default ReactDOM UMD build */
    const DEFAULT_REACTDOM = 'https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js';
    /** @var string default Babel standalone build */
    const DEFAULT_BABEL = 'https://cdn.jsdelivr.net/npm/@babel/standalone@7.26.10/babel.min.js';

    /** @var string sandbox flags used for the iframe attribute and the CSP header */
    const SANDBOX = 'allow-scripts allow-forms allow-modals allow-downloads';

    /**
     * @var array Libraries bundled in runtime/lib (math building blocks of bridge.js). They are only loaded if the
     *     generated code uses them (pattern), so other simulations stay small.
     */
    const MATH_LIBRARIES = [
        'katex' => [
            'pattern' => '/\bFormula\b|\bkatex\b|useKatex/',
            'styles' => ['katex/katex.min.css'],
            'scripts' => ['katex/katex.min.js'],
        ],
        'jsxgraph' => [
            'pattern' => '/\bMathBoard\b|\bJXG\b/',
            'styles' => ['jsxgraph/jsxgraph.css'],
            'scripts' => ['jsxgraph/jsxgraphcore.js'],
        ],
    ];

    /**
     * Builds the complete document.
     *
     * @param stdClass $simgen instance record
     * @param stdClass $version version record
     * @param bool $debug use development builds of React (better error messages for teachers)
     * @return string
     */
    public static function build(stdClass $simgen, stdClass $version, bool $debug = false): string {
        global $CFG;

        $config = [
            'parentOrigin' => self::origin($CFG->wwwroot),
            'codetype' => $version->codetype,
            'source' => $version->codetype === 'react' ? (string)$version->sourcecode : '',
            'css' => (string)($version->css ?? ''),
            // Public persona data only - the persona prompts stay on the server.
            'personas' => conversation::public_personas(conversation::from_version($version)),
            'strings' => self::strings(),
        ];
        $runtime = '<script>window.__SIMGEN__ = ' . self::json($config) . ';</script>' . "\n"
            . '<script>' . file_get_contents($CFG->dirroot . '/mod/simgen/runtime/bridge.js') . '</script>';

        if ($version->codetype === 'html') {
            return self::inject_into_document((string)$version->sourcecode, $runtime);
        }

        $libs = '';
        foreach (self::library_urls($debug) as $url) {
            $crossorigin = self::origin($url) !== self::origin($CFG->wwwroot) ? ' crossorigin="anonymous"' : '';
            $libs .= '<script src="' . s($url) . '"' . $crossorigin . '></script>' . "\n";
        }
        foreach (self::math_libraries((string)$version->sourcecode) as $name) {
            foreach (self::MATH_LIBRARIES[$name]['styles'] as $file) {
                $libs .= '<link rel="stylesheet" href="' . s(self::bundled_url($file)) . '">' . "\n";
            }
            foreach (self::MATH_LIBRARIES[$name]['scripts'] as $file) {
                $libs .= '<script src="' . s(self::bundled_url($file)) . '"></script>' . "\n";
            }
        }

        $lang = s($simgen->simlanguage ?: 'de');
        $title = s(format_string($simgen->name, true, ['escape' => false]));
        $basecss = file_get_contents($CFG->dirroot . '/mod/simgen/runtime/base.css');
        $loading = s(get_string('player_loading', 'mod_simgen'));

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>{$title}</title>
<style>{$basecss}</style>
{$runtime}
{$libs}</head>
<body>
<div id="simgen-loading" class="simgen-loading" role="status">{$loading}</div>
<div id="simgen-error" class="simgen-error" role="alert" hidden></div>
<div id="root"></div>
<script>window.__simgenStart && window.__simgenStart();</script>
</body>
</html>
HTML;
    }

    /**
     * Content-Security-Policy for the simulation document.
     *
     * The sandbox directive gives the document an opaque origin even if sim.php is opened directly,
     * connect-src 'none' prevents generated code from sending data anywhere.
     *
     * @return string
     */
    public static function csp(): string {
        global $CFG;
        $origins = [];
        foreach (self::library_urls(true) as $url) {
            $origins[self::origin($url)] = true;
        }
        $origins[self::origin($CFG->wwwroot)] = true;
        $scriptsrc = implode(' ', array_keys($origins));

        return implode('; ', [
            'sandbox ' . self::SANDBOX,
            "default-src 'none'",
            "script-src 'unsafe-inline' 'unsafe-eval' " . $scriptsrc,
            // Stylesheets of the bundled libraries (KaTeX embeds its fonts as data: URIs).
            "style-src 'unsafe-inline' " . self::origin($CFG->wwwroot),
            'img-src data: blob:',
            'font-src data:',
            'media-src data: blob:',
            "connect-src 'none'",
            "form-action 'none'",
            "base-uri 'none'",
            "frame-ancestors 'self'",
        ]);
    }

    /**
     * URLs of React, ReactDOM and Babel.
     *
     * @param bool $debug use the development builds of React if the URLs follow the npm UMD naming
     * @return string[]
     */
    public static function library_urls(bool $debug = false): array {
        $urls = [
            self::absolute(get_config('mod_simgen', 'libreact') ?: self::DEFAULT_REACT),
            self::absolute(get_config('mod_simgen', 'libreactdom') ?: self::DEFAULT_REACTDOM),
            self::absolute(get_config('mod_simgen', 'libbabel') ?: self::DEFAULT_BABEL),
        ];
        if ($debug) {
            $urls[0] = str_replace('.production.min.js', '.development.js', $urls[0]);
            $urls[1] = str_replace('.production.min.js', '.development.js', $urls[1]);
        }
        return $urls;
    }

    /**
     * Bundled libraries the generated code uses.
     *
     * @param string $source React source code
     * @return string[] keys of MATH_LIBRARIES
     */
    public static function math_libraries(string $source): array {
        $names = [];
        foreach (self::MATH_LIBRARIES as $name => $library) {
            if (preg_match($library['pattern'], $source)) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * URL of a file in runtime/lib (with the plugin version, so browsers reload it after an update).
     *
     * @param string $file path relative to runtime/lib
     * @return string
     */
    private static function bundled_url(string $file): string {
        global $CFG;
        return $CFG->wwwroot . '/mod/simgen/runtime/lib/' . $file . '?v=' . (int)get_config('mod_simgen', 'version');
    }

    /**
     * Injects the runtime into a complete HTML document.
     *
     * @param string $document
     * @param string $runtime
     * @return string
     */
    private static function inject_into_document(string $document, string $runtime): string {
        if (preg_match('/<head\b[^>]*>/i', $document, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            return substr($document, 0, $pos) . "\n" . $runtime . "\n" . substr($document, $pos);
        }
        if (preg_match('/<html\b[^>]*>/i', $document, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            return substr($document, 0, $pos) . "\n<head>" . $runtime . "</head>\n" . substr($document, $pos);
        }
        return "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\">" . $runtime . "</head><body>\n" . $document
            . "\n</body></html>";
    }

    /**
     * Localised strings used inside the iframe.
     *
     * @return array
     */
    private static function strings(): array {
        $keys = ['errortitle', 'errortext', 'errortextnonfatal', 'errordetails', 'reload', 'reset', 'close',
            'compileerror', 'noapp', 'libsfailed', 'conversationunavailable', 'conversationtimeout', 'unknownpersona'];
        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = get_string('player_' . $key, 'mod_simgen');
        }
        return $strings;
    }

    /**
     * JSON that is safe to embed into a <script> element.
     *
     * @param mixed $data
     * @return string
     */
    private static function json($data): string {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Makes site relative URLs (starting with /) absolute.
     *
     * @param string $url
     * @return string
     */
    private static function absolute(string $url): string {
        global $CFG;
        $url = trim($url);
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return $CFG->wwwroot . $url;
        }
        return $url;
    }

    /**
     * Origin (scheme://host[:port]) of a URL.
     *
     * @param string $url
     * @return string
     */
    public static function origin(string $url): string {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $scheme = strtolower($parts['scheme']);
        $port = (int)($parts['port'] ?? 0);
        // Browsers omit default ports in event.origin.
        if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) {
            $port = 0;
        }
        return $scheme . '://' . strtolower($parts['host']) . ($port ? ':' . $port : '');
    }
}
