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
 * Upgrade file.
 *
 * @package    mod_status
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade steps for status.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_status_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026100501) {
        $functions = [
            "mod_status_set_status",
            "mod_status_get_dashboard",
        ];

        foreach ($functions as $functionname) {
            $DB->set_field(
                "external_functions",
                "methodname",
                "execute",
                ["name" => $functionname, "component" => "mod_status"]
            );
        }

        upgrade_mod_savepoint(true, 2026100501, "status");
    }

    return true;
}
