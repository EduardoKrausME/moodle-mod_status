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
 * restore_status_stepslib.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_status_activity_structure_step extends restore_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return array Return value.
     */
    protected function define_structure(): array {
        $paths = [new restore_path_element("status", "/activity/status")];
        if ($this->get_setting_value("userinfo")) {
            $paths[] = new restore_path_element("status_user", "/activity/status/responses/response");
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Method process_status.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_status($data): void {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record("status", $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Method process_status_user.
     *
     * @param mixed $data Parameter data.
     * @return void Return value.
     */
    protected function process_status_user($data): void {
        global $DB;
        $data = (object)$data;
        $data->statusid = $this->get_new_parentid("status");
        $data->userid = $this->get_mappingid("user", $data->userid);
        if ($data->userid) {
            $newitemid = $DB->insert_record("status_user", $data);
            $this->set_mapping("status_user", $data->id, $newitemid);
        }
    }

    /**
     * Method after_execute.
     *
     * @return void Return value.
     */
    protected function after_execute(): void {
        $this->add_related_files("mod_status", "intro", null);
    }
}
