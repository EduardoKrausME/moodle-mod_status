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
 * set_status.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_status\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_status\event\status_changed;
use mod_status\status_manager;

/**
 * Class set_status.
 */
class set_status extends external_api {
    /**
     * Method execute_parameters.
     *
     * @return external_function_parameters Return value.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            "cmid" => new external_value(PARAM_INT, "Course module id"),
            "statekey" => new external_value(PARAM_ALPHANUMEXT, "State key"),
        ]);
    }

    /**
     * Method execute.
     *
     * @param int $cmid Parameter cmid.
     * @param string $statekey Parameter statekey.
     * @return array Return value.
     */
    public static function execute(int $cmid, string $statekey): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ["cmid" => $cmid, "statekey" => $statekey]);
        $cm = get_coursemodule_from_id("status", $params["cmid"], 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_login($course, false, $cm);
        require_capability("mod/status:setstatus", $context);

        $activity = $DB->get_record("status", ["id" => $cm->instance], "*", MUST_EXIST);
        $manager = new status_manager($activity, $cm, $context);
        $record = $manager->set_user_status($USER->id, $params["statekey"]);
        $state = $manager->find_state($params["statekey"]);

        status_changed::create([
            "objectid" => $record->id,
            "context" => $context,
            "other" => ["statekey" => $params["statekey"]],
        ])->trigger();

        return [
            "statekey" => $params["statekey"],
            "label" => $state["label"],
            "timemodified" => $record->timemodified,
        ];
    }

    /**
     * Method execute_returns.
     *
     * @return external_single_structure Return value.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            "statekey" => new external_value(PARAM_ALPHANUMEXT, "State key"),
            "label" => new external_value(PARAM_TEXT, "State label"),
            "timemodified" => new external_value(PARAM_INT, "Modification time"),
        ]);
    }
}
