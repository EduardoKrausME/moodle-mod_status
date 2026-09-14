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
 * backup_status_stepslib.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_status_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $status = new backup_nested_element("status", ["id"], [
            "name", "intro", "introformat", "states", "completionstatusset", "timecreated", "timemodified",
        ]);
        $responses = new backup_nested_element("responses");
        $response = new backup_nested_element("response", ["id"], [
            "userid", "statekey", "timecreated", "timemodified",
        ]);

        $status->add_child($responses);
        $responses->add_child($response);

        $status->set_source_table("status", ["id" => backup::VAR_ACTIVITYID]);
        $response->set_source_table("status_user", ["statusid" => backup::VAR_PARENTID]);
        $response->annotate_ids("user", "userid");
        $status->annotate_files("mod_status", "intro", null);

        return $this->prepare_activity_structure($status);
    }
}
