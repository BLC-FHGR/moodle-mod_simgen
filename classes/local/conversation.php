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

use context_module;
use moodle_exception;
use stdClass;

/**
 * Live conversations of learners with AI personas.
 *
 * The personas (incl. their prompts) are generated together with the code and stored server side with the
 * version. The simulation only sends a persona id and the learner's message; history and persona prompt are
 * taken from the database, so learners cannot change the role or inject a different history.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conversation {

    /** @var int default upper limit of messages per conversation (admin setting maxturns) */
    const DEFAULT_MAX_TURNS = 20;
    /** @var int default number of messages per persona if the AI does not define it */
    const DEFAULT_PERSONA_TURNS = 10;
    /** @var int default maximum length of a learner message (admin setting maxmessagelength) */
    const DEFAULT_MAX_MESSAGE_LENGTH = 1000;
    /** @var int number of previous messages included in the prompt */
    const HISTORY_LIMIT = 40;
    /** @var int maximum number of personas per version */
    const MAX_PERSONAS = 8;
    /** @var int maximum number of signals per persona */
    const MAX_SIGNALS = 10;

    /** @var string built-in signal: the conversation came to a natural end (requested in every persona prompt) */
    const END_SIGNAL = 'beendet';

    /** @var string description of the built-in end signal for the AI */
    const END_SIGNAL_DESCRIPTION = 'true, wenn das Gespräch zu Ende ist – du hast dich verabschiedet oder gehst weg, '
        . 'die lernende Person hat sich verabschiedet bzw. das Gespräch beendet, oder dein Anliegen ist endgültig erledigt '
        . 'oder abgelehnt und es gibt nichts mehr zu besprechen; sonst false';

    /**
     * Validates and normalises persona definitions (JSON string or decoded array).
     *
     * Accepted: {"personas": [...]} or [...]. Invalid entries are skipped.
     *
     * @param mixed $data
     * @return array personas keyed by id
     * @throws moodle_exception if the JSON cannot be decoded
     */
    public static function normalise_personas($data): array {
        if (is_string($data)) {
            $data = trim($data);
            if ($data === '') {
                return [];
            }
            $decoded = json_decode($data, true);
            if (!is_array($decoded)) {
                throw new moodle_exception('error_personasjson', 'mod_simgen', '', json_last_error_msg());
            }
            $data = $decoded;
        }
        if (!is_array($data)) {
            throw new moodle_exception('error_personasjson', 'mod_simgen', '', 'not an object');
        }
        $list = array_key_exists('personas', $data) ? $data['personas'] : $data;
        if (!is_array($list)) {
            throw new moodle_exception('error_personasjson', 'mod_simgen', '', '"personas" is not a list');
        }

        $cap = self::max_turns_cap();
        $str = fn($value): string => is_scalar($value) ? trim((string)$value) : '';
        $personas = [];
        foreach ($list as $p) {
            if (!is_array($p) || count($personas) >= self::MAX_PERSONAS) {
                continue;
            }
            $id = self::normalise_id($str($p['id'] ?? ''));
            $prompt = $str($p['prompt'] ?? '');
            if ($id === '' || $prompt === '' || isset($personas[$id])) {
                continue;
            }

            $signals = [];
            if (!empty($p['signals']) && is_array($p['signals'])) {
                foreach ($p['signals'] as $key => $description) {
                    if (is_int($key) && is_string($description)) {
                        // List form: ["stimmung", "geloest"].
                        $key = $description;
                        $description = '';
                    }
                    // Keep case and underscores, so the keys match what the generated code reads (chat.signals.x).
                    $key = substr(preg_replace('/[^A-Za-z0-9_]/', '', (string)$key), 0, 30);
                    if ($key === '' || count($signals) >= self::MAX_SIGNALS) {
                        continue;
                    }
                    $signals[$key] = \core_text::substr($str($description), 0, 300);
                }
            }

            $maxturns = (int)($p['maxTurns'] ?? $p['maxturns'] ?? 0);
            $personas[$id] = [
                'id' => $id,
                'name' => \core_text::substr(clean_param($str($p['name'] ?? '') ?: $id, PARAM_TEXT), 0, 100),
                'role' => \core_text::substr(clean_param($str($p['role'] ?? ''), PARAM_TEXT), 0, 200),
                'prompt' => \core_text::substr($prompt, 0, 8000),
                'opening' => \core_text::substr($str($p['opening'] ?? ''), 0, 1000),
                'maxturns' => max(1, min($cap, $maxturns > 0 ? $maxturns : self::DEFAULT_PERSONA_TURNS)),
                'signals' => $signals,
            ];
        }
        return $personas;
    }

    /**
     * Normalises a persona id. Must stay identical to normaliseId() in runtime/bridge.js.
     *
     * @param string $id
     * @return string
     */
    public static function normalise_id(string $id): string {
        return substr(preg_replace('/[^a-z0-9_-]/', '', strtolower($id)), 0, 50);
    }

    /**
     * Maximum number of restarts per conversation.
     *
     * @return int
     */
    public static function max_resets(): int {
        $value = get_config('mod_simgen', 'maxresets');
        return $value === false || $value === '' ? 3 : max(0, (int)$value);
    }

    /**
     * Personas of a version (empty if none or invalid).
     *
     * @param stdClass $version
     * @return array
     */
    public static function from_version(stdClass $version): array {
        if (empty($version->personas)) {
            return [];
        }
        try {
            return self::normalise_personas((string)$version->personas);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Persona information that may be sent to the browser (without the prompts).
     *
     * @param array $personas
     * @return array
     */
    public static function public_personas(array $personas): array {
        $result = [];
        foreach ($personas as $p) {
            $result[] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'role' => $p['role'],
                'opening' => $p['opening'],
                'maxTurns' => $p['maxturns'],
                'signals' => array_keys($p['signals']),
            ];
        }
        return $result;
    }

    /**
     * Upper limit of messages per conversation.
     *
     * @return int
     */
    public static function max_turns_cap(): int {
        $value = (int)get_config('mod_simgen', 'maxturns');
        return $value > 0 ? $value : self::DEFAULT_MAX_TURNS;
    }

    /**
     * Maximum length of a learner message.
     *
     * @return int
     */
    public static function max_message_length(): int {
        $value = (int)get_config('mod_simgen', 'maxmessagelength');
        return $value > 0 ? $value : self::DEFAULT_MAX_MESSAGE_LENGTH;
    }

    /**
     * Version a user may talk to: the published one, or (for teachers) the previewed one.
     *
     * @param stdClass $simgen
     * @param int $versionid requested version
     * @param context_module $context
     * @return stdClass
     */
    public static function resolve_version(stdClass $simgen, int $versionid, context_module $context): stdClass {
        global $DB;
        if (!$versionid || !has_capability('mod/simgen:manage', $context)) {
            $versionid = (int)$simgen->publishedversion;
        }
        $version = $versionid ? $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id]) : false;
        if (!$version) {
            throw new moodle_exception('error_noversion', 'mod_simgen');
        }
        return $version;
    }

    /**
     * A persona of a version.
     *
     * @param stdClass $version
     * @param string $personaid
     * @return array
     */
    public static function get_persona(stdClass $version, string $personaid): array {
        $personas = self::from_version($version);
        if (!isset($personas[$personaid])) {
            throw new moodle_exception('error_unknownpersona', 'mod_simgen', '', s($personaid));
        }
        return $personas[$personaid];
    }

    /**
     * Stored conversation of a user.
     *
     * @param int $simgenid
     * @param int $userid
     * @param int $versionid
     * @param string $personaid
     * @return stdClass|null
     */
    public static function get_record(int $simgenid, int $userid, int $versionid, string $personaid): ?stdClass {
        global $DB;
        $record = $DB->get_record('simgen_conversations', [
            'simgenid' => $simgenid,
            'userid' => $userid,
            'versionid' => $versionid,
            'personaid' => $personaid,
        ]);
        return $record ?: null;
    }

    /**
     * Data returned to the simulation.
     *
     * @param stdClass|null $record
     * @param array $persona
     * @param string $reply latest answer of the persona
     * @return array
     */
    public static function export(?stdClass $record, array $persona, string $reply = ''): array {
        $messages = [];
        if ($persona['opening'] !== '') {
            $messages[] = ['role' => 'persona', 'text' => $persona['opening']];
        }
        foreach (self::decode_list($record->messagedata ?? null) as $m) {
            $messages[] = ['role' => ($m['role'] ?? '') === 'user' ? 'user' : 'persona', 'text' => (string)($m['text'] ?? '')];
        }
        $turns = $record ? (int)$record->turncount : 0;
        $signals = self::decode_list($record->signaldata ?? null);
        $ended = self::is_ended($record);
        if (!isset($persona['signals'][self::END_SIGNAL])) {
            // Built-in signal: exposed as "ended", not as a signal of the simulation.
            unset($signals[self::END_SIGNAL]);
        }

        return [
            'reply' => $reply,
            'messages' => self::encode($messages),
            'signals' => self::encode((object)$signals),
            'turn' => $turns,
            'maxturns' => (int)$persona['maxturns'],
            'ended' => $ended,
            // Done: message limit reached OR the conversation came to a natural end.
            'done' => $ended || $turns >= (int)$persona['maxturns'],
        ];
    }

    /**
     * Whether a conversation came to a natural end (persona said goodbye, learner ended it, ...).
     *
     * @param stdClass|null $record
     * @return bool
     */
    public static function is_ended(?stdClass $record): bool {
        $signals = self::decode_list($record->signaldata ?? null);
        return ($signals[self::END_SIGNAL] ?? false) === true;
    }

    /**
     * Signals the AI estimates after each answer: those of the persona plus the built-in end signal.
     *
     * @param array $persona
     * @return array key => description
     */
    public static function signal_spec(array $persona): array {
        $spec = $persona['signals'];
        if (!isset($spec[self::END_SIGNAL])) {
            $spec[self::END_SIGNAL] = self::END_SIGNAL_DESCRIPTION;
        }
        return $spec;
    }

    /**
     * Ends a conversation on behalf of the learner (button "end conversation"), without an AI request.
     *
     * @param stdClass $simgen
     * @param stdClass $version
     * @param array $persona
     * @param int $userid
     * @return array see export()
     */
    public static function end(stdClass $simgen, stdClass $version, array $persona, int $userid): array {
        global $DB;

        $lock = self::lock((int)$simgen->id, $userid, $persona['id']);
        try {
            $record = self::get_record((int)$simgen->id, $userid, (int)$version->id, $persona['id']);
            $signals = self::decode_list($record->signaldata ?? null);
            $signals[self::END_SIGNAL] = true;
            $now = time();
            if ($record) {
                $record->signaldata = self::encode((object)$signals);
                $record->timemodified = $now;
                $DB->update_record('simgen_conversations', $record);
            } else {
                $record = (object)[
                    'simgenid' => $simgen->id,
                    'userid' => $userid,
                    'versionid' => $version->id,
                    'personaid' => $persona['id'],
                    'messagedata' => null,
                    'signaldata' => self::encode((object)$signals),
                    'turncount' => 0,
                    'resetcount' => 0,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $record->id = $DB->insert_record('simgen_conversations', $record);
            }
            return self::export($record, $persona);
        } finally {
            $lock->release();
        }
    }

    /**
     * Sends a learner message to the persona and stores both messages.
     *
     * @param stdClass $simgen
     * @param stdClass $version
     * @param context_module $context
     * @param array $persona
     * @param int $userid
     * @param string $message
     * @return array see export()
     */
    public static function send(stdClass $simgen, stdClass $version, context_module $context, array $persona, int $userid,
            string $message): array {
        global $DB;

        $message = trim(str_replace(["\r\n", "\r"], "\n", $message));
        $maxlength = self::max_message_length();
        if ($message === '') {
            throw new moodle_exception('error_emptymessage', 'mod_simgen');
        }
        if (\core_text::strlen($message) > $maxlength) {
            throw new moodle_exception('error_messagetoolong', 'mod_simgen', '', $maxlength);
        }

        // One request per conversation at a time (e.g. two browser tabs): read, AI call and write under a lock.
        $lock = self::lock((int)$simgen->id, $userid, $persona['id']);
        try {
            $record = self::get_record((int)$simgen->id, $userid, (int)$version->id, $persona['id']);
            $turns = $record ? (int)$record->turncount : 0;
            if ($turns >= (int)$persona['maxturns'] || self::is_ended($record)) {
                throw new moodle_exception('error_conversationfinished', 'mod_simgen');
            }

            $history = self::decode_list($record->messagedata ?? null);
            $prompt = self::build_prompt($simgen, $persona, $history, $message);

            $purpose = get_config('mod_simgen', 'conversationpurpose') ?: 'chat';
            $manager = new \local_ai_manager\manager($purpose);
            $result = $manager->perform_request($prompt, 'mod_simgen', $context->id);
            if ($result->get_code() !== 200) {
                throw new moodle_exception('error_conversationai', 'mod_simgen', '', $result->get_errormessage());
            }

            [$reply, $signals] = self::parse_reply($result->get_content(), $persona);
            if ($reply === '') {
                throw new moodle_exception('error_emptyreply', 'mod_simgen');
            }

            $now = time();
            $history[] = ['role' => 'user', 'text' => $message, 'time' => $now];
            $history[] = ['role' => 'persona', 'text' => $reply, 'time' => $now];
            $signals = array_replace(self::decode_list($record->signaldata ?? null), $signals);

            $data = [
                'messagedata' => self::encode($history),
                'signaldata' => self::encode((object)$signals),
                'turncount' => $turns + 1,
                'timemodified' => $now,
            ];
            if ($record) {
                $data['id'] = $record->id;
                $DB->update_record('simgen_conversations', (object)$data);
                $record = (object)array_merge((array)$record, $data);
            } else {
                $record = (object)array_merge($data, [
                    'simgenid' => $simgen->id,
                    'userid' => $userid,
                    'versionid' => $version->id,
                    'personaid' => $persona['id'],
                    'resetcount' => 0,
                    'timecreated' => $now,
                ]);
                $record->id = $DB->insert_record('simgen_conversations', $record);
            }
        } finally {
            $lock->release();
        }

        return self::export($record, $persona, $reply);
    }

    /**
     * Starts a conversation again. The number of restarts is limited (admin setting maxresets), so that the
     * message limit per conversation really limits the number of AI requests.
     *
     * @param stdClass $simgen
     * @param stdClass $version
     * @param array $persona
     * @param int $userid
     * @return array see export()
     */
    public static function reset(stdClass $simgen, stdClass $version, array $persona, int $userid): array {
        global $DB;

        $lock = self::lock((int)$simgen->id, $userid, $persona['id']);
        try {
            $record = self::get_record((int)$simgen->id, $userid, (int)$version->id, $persona['id']);
            if (!$record || ((int)$record->turncount === 0 && empty($record->messagedata) && empty($record->signaldata))) {
                // Nothing to reset.
                return self::export($record, $persona);
            }
            if ((int)$record->resetcount >= self::max_resets()) {
                throw new moodle_exception('error_resetlimit', 'mod_simgen');
            }
            $record->messagedata = null;
            $record->signaldata = null;
            $record->turncount = 0;
            $record->resetcount = (int)$record->resetcount + 1;
            $record->timemodified = time();
            $DB->update_record('simgen_conversations', $record);
            return self::export($record, $persona);
        } finally {
            $lock->release();
        }
    }

    /**
     * Lock for one conversation of a user.
     *
     * @param int $simgenid
     * @param int $userid
     * @param string $personaid
     * @return \core\lock\lock
     */
    private static function lock(int $simgenid, int $userid, string $personaid): \core\lock\lock {
        $factory = \core\lock\lock_config::get_lock_factory('mod_simgen');
        $lock = $factory->get_lock('conversation_' . $simgenid . '_' . $userid . '_' . $personaid, 5);
        if (!$lock) {
            throw new moodle_exception('error_conversationbusy', 'mod_simgen');
        }
        return $lock;
    }

    /**
     * Builds the prompt for the persona.
     *
     * @param stdClass $simgen
     * @param array $persona
     * @param array $history stored messages
     * @param string $message new learner message
     * @return string
     */
    public static function build_prompt(stdClass $simgen, array $persona, array $history, string $message): string {
        $lines = [];
        if ($persona['opening'] !== '') {
            $lines[] = $persona['name'] . ': ' . $persona['opening'];
        }
        foreach (array_slice($history, -self::HISTORY_LIMIT) as $m) {
            $speaker = ($m['role'] ?? '') === 'user' ? 'Lernende Person' : $persona['name'];
            $lines[] = $speaker . ': ' . str_replace("\n", ' ', (string)($m['text'] ?? ''));
        }

        // Always at least the built-in end signal, so a natural end of the conversation is detected.
        $spec = [];
        $example = [];
        foreach (self::signal_spec($persona) as $key => $description) {
            $spec[] = '- ' . $key . ': ' . ($description !== '' ? $description : 'Einschätzung aus Sicht deiner Rolle');
            $example[] = '"' . $key . '": <Wert>';
        }
        $statusrules = "## Einschätzung nach deiner Antwort\n"
            . "Schätze nach deiner Antwort aus Sicht deiner Rolle folgende Werte ein:\n" . implode("\n", $spec) . "\n\n"
            . "Schreibe als allerletzte Zeile genau eine Statuszeile in diesem Format (gültiges JSON in einer Zeile, "
            . "Zahlen als Zahlen, ja/nein als true/false):\n"
            . 'STATUS: {' . implode(', ', $example) . '}';

        $none = '(keine Angabe)';
        return strtr(prompt_templates::CONVERSATION, [
            '{{TITLE}}' => trim((string)$simgen->name) ?: $none,
            '{{SUBJECT}}' => trim((string)$simgen->subject) ?: $none,
            '{{TARGETGROUP}}' => trim((string)$simgen->targetgroup) ?: $none,
            '{{LEARNINGOUTCOMES}}' => trim((string)$simgen->learningoutcomes) ?: $none,
            '{{LANGUAGE}}' => options::LANGUAGES[$simgen->simlanguage] ?? 'Deutsch',
            '{{NAME}}' => $persona['name'],
            '{{ROLE}}' => $persona['role'] !== '' ? $persona['role'] : $none,
            '{{PERSONA}}' => $persona['prompt'],
            '{{HISTORY}}' => $lines ? implode("\n", $lines) : '(Das Gespräch beginnt jetzt.)',
            '{{MESSAGE}}' => $message,
            '{{STATUS_RULES}}' => $statusrules,
        ]);
    }

    /**
     * Separates the answer of the persona from the status line.
     *
     * @param string $content AI response (HTML from local_ai_manager or plain text)
     * @param array $persona
     * @return array [string reply, array signals]
     */
    public static function parse_reply(string $content, array $persona): array {
        $text = str_replace(["\r\n", "\r"], "\n", $content);
        if (preg_match('#</?(p|br|div|span|strong|em|b|i|ul|ol|li|pre|code|h[1-6]|blockquote)\b[^>]*>#i', $text)) {
            // local_ai_manager returns Markdown converted to HTML: convert it back to plain text ourselves
            // (keeps the text as it is, unlike html_to_text which e.g. upper-cases bold text).
            $text = preg_replace('#<br\s*/?>#i', "\n", $text);
            $text = preg_replace('#</(p|div|li|pre|blockquote|h[1-6])>#i', "\n", $text);
            $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $signals = [];
        if (preg_match_all('/[*_`]*STATUS[*_`]*\s*:?\s*(\{[^{}]*\})[*_`]*/i', $text, $matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $last = end($matches);
            $decoded = json_decode($last[1][0], true);
            if (is_array($decoded)) {
                foreach (array_keys(self::signal_spec($persona)) as $key) {
                    if (array_key_exists($key, $decoded)) {
                        $value = self::clean_signal($decoded[$key]);
                        if ($value !== null) {
                            $signals[$key] = $value;
                        }
                    }
                }
            }
            // Remove all status lines from the answer.
            foreach (array_reverse($matches) as $match) {
                $text = substr($text, 0, $match[0][1]) . substr($text, $match[0][1] + strlen($match[0][0]));
            }
        }

        $text = trim($text);
        // Remove a speaker label like "Frau Keller:" and wrapping quotes.
        $text = preg_replace('/^[*_]*' . preg_quote($persona['name'], '/') . '[*_]*\s*:\s*/u', '', $text);
        $text = trim(preg_replace('/\n{3,}/', "\n\n", $text));
        if (preg_match('/^["„«“](.*)["“»”]$/us', $text, $m) && !preg_match('/["„«“»”]/u', $m[1])) {
            $text = trim($m[1]);
        }
        return [\core_text::substr($text, 0, 3000), $signals];
    }

    /**
     * Sanitises a signal value.
     *
     * @param mixed $value
     * @return bool|int|float|string|null
     */
    private static function clean_signal($value) {
        if (is_bool($value) || is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return is_finite($value) ? $value : null;
        }
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if ($lower === 'true' || $lower === 'ja') {
                return true;
            }
            if ($lower === 'false' || $lower === 'nein') {
                return false;
            }
            if (is_numeric($lower)) {
                $number = $lower + 0;
                return is_finite((float)$number) ? $number : null;
            }
            return \core_text::substr(clean_param($value, PARAM_TEXT), 0, 100);
        }
        return null;
    }

    /**
     * Decodes a JSON list/object column.
     *
     * @param string|null $json
     * @return array
     */
    private static function decode_list(?string $json): array {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * JSON encoding for the database and the web services.
     *
     * @param mixed $data
     * @return string
     */
    private static function encode($data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $json === false ? (is_object($data) ? '{}' : '[]') : $json;
    }
}
