<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * status_manager.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_status;

use context_module;
use core_text;
use stdClass;

/**
 * Class status_manager.
 */
class status_manager {
    /**
     * Property activity.
     *
     * @var stdClass
     */
    private stdClass $activity;
    /**
     * Property cm.
     *
     * @var stdClass
     */
    private stdClass $cm;
    /**
     * Property context.
     *
     * @var context_module
     */
    private context_module $context;

    /**
     * Method __construct.
     *
     * @param stdClass $activity Parameter activity.
     * @param stdClass $cm Parameter cm.
     * @param context_module $context Parameter context.
     */
    public function __construct(stdClass $activity, stdClass $cm, context_module $context) {
        $this->activity = $activity;
        $this->cm = $cm;
        $this->context = $context;
    }

    /**
     * Method parse_labels.
     *
     * @param string $raw Parameter raw.
     * @return array Return value.
     */
    public static function parse_labels(string $raw): array {
        $lines = preg_split('/\R/u', $raw) ?: [];
        $labels = [];
        foreach ($lines as $line) {
            $label = trim(clean_param($line, PARAM_TEXT));
            if ($label !== "") {
                $labels[] = core_text::substr($label, 0, 100);
            }
        }
        return $labels;
    }

    /**
     * Method build_states.
     *
     * @param array $labels Parameter labels.
     * @param array $existing Parameter existing.
     * @return array Return value.
     */
    public static function build_states(array $labels, array $existing = []): array {
        $existingbylabel = [];
        foreach ($existing as $state) {
            if (isset($state["label"], $state["key"])) {
                $existingbylabel[core_text::strtolower(trim($state["label"]))] = $state["key"];
            }
        }

        $states = [];
        foreach ($labels as $position => $label) {
            $normalized = core_text::strtolower(trim($label));
            $key = $existingbylabel[$normalized] ?? self::new_key($label, $position);
            $states[] = ["key" => $key, "label" => $label];
        }
        return $states;
    }

    /**
     * Method new_key.
     *
     * @param string $label Parameter label.
     * @param int $position Parameter position.
     * @return string Return value.
     */
    private static function new_key(string $label, int $position): string {
        return "s_" . substr(hash("sha256", $label . "|" . $position . "|" . microtime(true)), 0, 20);
    }

    /**
     * Method encode_states.
     *
     * @param array $states Parameter states.
     * @return string Return value.
     */
    public static function encode_states(array $states): string {
        return json_encode(array_values($states), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Method decode_states.
     *
     * @param string $json Parameter json.
     * @return array Return value.
     */
    public static function decode_states(string $json): array {
        $states = json_decode($json, true);
        return is_array($states) ? array_values($states) : [];
    }

    /**
     * Method get_states.
     *
     * @return array Return value.
     */
    public function get_states(): array {
        return self::decode_states($this->activity->states);
    }

    /**
     * Method set_user_status.
     *
     * @param int $userid Parameter userid.
     * @param string $statekey Parameter statekey.
     * @return stdClass Return value.
     */
    public function set_user_status(int $userid, string $statekey): stdClass {
        global $DB;

        $state = $this->find_state($statekey);
        if ($state === null) {
            throw new \moodle_exception("invalidstate", "mod_status");
        }

        $now = time();
        $record = $DB->get_record("status_user", ["statusid" => $this->activity->id, "userid" => $userid]);
        if ($record) {
            $record->statekey = $statekey;
            $record->timemodified = $now;
            $DB->update_record("status_user", $record);
        } else {
            $record = (object)[
                "statusid" => $this->activity->id,
                "userid" => $userid,
                "statekey" => $statekey,
                "timecreated" => $now,
                "timemodified" => $now,
            ];
            $record->id = $DB->insert_record("status_user", $record);
        }

        $course = get_course($this->cm->course);
        $completion = new \completion_info($course);
        if ($completion->is_enabled($this->cm)) {
            $completion->update_state($this->cm, COMPLETION_UNKNOWN, $userid);
        }

        return $record;
    }

    /**
     * Method get_user_status.
     *
     * @param int $userid Parameter userid.
     * @return ?stdClass Return value.
     */
    public function get_user_status(int $userid): ?stdClass {
        global $DB;
        $record = $DB->get_record("status_user", ["statusid" => $this->activity->id, "userid" => $userid]);
        return $record ?: null;
    }

    /**
     * Method get_student_template_data.
     *
     * @param int $userid Parameter userid.
     * @return array Return value.
     */
    public function get_student_template_data(int $userid): array {
        $current = $this->get_user_status($userid);
        $states = [];
        foreach ($this->get_states() as $state) {
            $state["active"] = $current && $current->statekey === $state["key"];
            $states[] = $state;
        }
        return [
            "states" => $states,
            "hascurrent" => (bool)$current,
            "currentlabel" => $current ? ($this->find_state($current->statekey)["label"] ?? "") : "",
        ];
    }

    /**
     * Method get_dashboard_template_data.
     *
     * @return array Return value.
     */
    public function get_dashboard_template_data(): array {
        global $DB;

        $states = $this->get_states();
        $users = get_enrolled_users(
            $this->context,
            "mod/status:setstatus",
            0,
            "u.id,u.firstname,u.lastname,u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename",
            "u.lastname ASC, u.firstname ASC"
        );
        $responses = $DB->get_records("status_user", ["statusid" => $this->activity->id], "", "userid,statekey,timemodified");

        $buckets = [];
        foreach ($states as $state) {
            $buckets[$state["key"]] = [
                "key" => $state["key"],
                "label" => $state["label"],
                "count" => 0,
                "users" => [],
                "hasusers" => false,
            ];
        }
        $notset = [];

        foreach ($users as $user) {
            $response = $responses[$user->id] ?? null;
            $item = [
                "id" => $user->id,
                "fullname" => fullname($user),
                "initials" => self::initials(fullname($user)),
            ];
            if ($response && isset($buckets[$response->statekey])) {
                $buckets[$response->statekey]["users"][] = $item;
                $buckets[$response->statekey]["count"]++;
                $buckets[$response->statekey]["hasusers"] = true;
            } else {
                $notset[] = $item;
            }
        }

        return [
            "states" => array_values($buckets),
            "notset" => $notset,
            "notsetcount" => count($notset),
            "hasnotset" => !empty($notset),
            "total" => count($users),
            "updatedat" => userdate(time(), get_string("strftimetime24", "langconfig")),
        ];
    }

    /**
     * Method find_state.
     *
     * @param string $key Parameter key.
     * @return ?array Return value.
     */
    public function find_state(string $key): ?array {
        foreach ($this->get_states() as $state) {
            if (($state["key"] ?? "") === $key) {
                return $state;
            }
        }
        return null;
    }

    /**
     * Method initials.
     *
     * @param string $fullname Parameter fullname.
     * @return string Return value.
     */
    private static function initials(string $fullname): string {
        $parts = preg_split('/\s+/u', trim($fullname)) ?: [];
        if (!$parts) {
            return "?";
        }
        $first = core_text::substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? core_text::substr($parts[count($parts) - 1], 0, 1) : "";
        return core_text::strtoupper($first . $last);
    }
}
