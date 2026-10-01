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
 * status_changed.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_status\event;

use core\event\base;
use moodle_url;

/**
 * Class status_changed.
 */
class status_changed extends base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data["crud"] = "u";
        $this->data["edulevel"] = self::LEVEL_PARTICIPATING;
        $this->data["objecttable"] = "status_user";
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string("eventstatuschanged", "mod_status");
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' changed their status in activity '{$this->contextinstanceid}'.";
    }

    /**
     * Method get_url.
     *
     * @return moodle_url Return value.
     */
    public function get_url(): moodle_url {
        return new moodle_url("/mod/status/view.php", ["id" => $this->contextinstanceid]);
    }

    /**
     * Method get_objectid_mapping.
     *
     * @return array Return value.
     */
    public static function get_objectid_mapping(): array {
        return ["db" => "status_user", "restore" => "status_user"];
    }

    /**
     * Method get_other_mapping.
     *
     * @return mixed Return value.
     */
    public static function get_other_mapping() {
        return false;
    }
}
