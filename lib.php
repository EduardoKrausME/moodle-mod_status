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
 * lib.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_status\instance_manager;

/**
 * Returns the features supported by this activity.
 *
 * @param string $feature
 * @return mixed
 */
function status_supports(string $feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_COMPLETION => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_COLLABORATION,
        default => null,
    };
}

/**
 * Creates an activity instance.
 *
 * @param stdClass $data
 * @param mod_status_mod_form|null $mform
 * @return int
 */
function status_add_instance(stdClass $data, ?mod_status_mod_form $mform = null): int {
    return instance_manager::add($data);
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data
 * @param mod_status_mod_form|null $mform
 * @return bool
 */
function status_update_instance(stdClass $data, ?mod_status_mod_form $mform = null): bool {
    return instance_manager::update($data);
}

/**
 * Deletes an activity instance.
 *
 * @param int $id
 * @return bool
 */
function status_delete_instance(int $id): bool {
    return instance_manager::delete($id);
}


/**
 * Adds cached information used by course pages and custom completion.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function status_get_coursemodule_info(stdClass $coursemodule) {
    global $DB;

    $status = $DB->get_record("status", ["id" => $coursemodule->instance],
        "id,name,intro,introformat,completionstatusset");
    if (!$status) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $status->name;
    if ($coursemodule->showdescription) {
        $result->content = format_module_intro("status", $status, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata["customcompletionrules"]["completionstatusset"] = $status->completionstatusset;
    }
    return $result;
}
