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
 * instance_manager.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_status;

/**
 * Class instance_manager.
 */
class instance_manager {
    /**
     * Method add.
     *
     * @param \stdClass $data Parameter data.
     * @return int Return value.
     */
    public static function add(\stdClass $data): int {
        global $DB;

        $labels = status_manager::parse_labels((string)$data->statuslabels);
        $data->states = status_manager::encode_states(status_manager::build_states($labels));
        unset($data->statuslabels);
        $data->timecreated = time();
        $data->timemodified = $data->timecreated;
        return (int)$DB->insert_record("status", $data);
    }

    /**
     * Method update.
     *
     * @param \stdClass $data Parameter data.
     * @return bool Return value.
     */
    public static function update(\stdClass $data): bool {
        global $DB;

        $data->id = $data->instance;
        $current = $DB->get_record("status", ["id" => $data->id], "id, states", MUST_EXIST);
        $oldstates = status_manager::decode_states($current->states);
        $labels = status_manager::parse_labels((string)$data->statuslabels);
        $newstates = status_manager::build_states($labels, $oldstates);
        $data->states = status_manager::encode_states($newstates);
        unset($data->statuslabels);
        $data->timemodified = time();

        $validkeys = array_column($newstates, "key");
        if ($validkeys) {
            [$insql, $params] = $DB->get_in_or_equal($validkeys, SQL_PARAMS_NAMED, "sk", false);
            $params["statusid"] = $data->id;
            $DB->delete_records_select("status_user", "statusid = :statusid AND statekey {$insql}", $params);
        } else {
            $DB->delete_records("status_user", ["statusid" => $data->id]);
        }

        $updated = $DB->update_record("status", $data);

        $cm = get_coursemodule_from_instance("status", $data->id, $data->course ?? 0, false, IGNORE_MISSING);
        if ($cm) {
            $course = get_course($cm->course);
            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->reset_all_state($cm);
            }
        }

        return $updated;
    }

    /**
     * Method delete.
     *
     * @param int $id Parameter id.
     * @return bool Return value.
     */
    public static function delete(int $id): bool {
        global $DB;

        if (!$DB->record_exists("status", ["id" => $id])) {
            return false;
        }
        $DB->delete_records("status_user", ["statusid" => $id]);
        $DB->delete_records("status", ["id" => $id]);
        return true;
    }
}
