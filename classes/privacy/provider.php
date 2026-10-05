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
 * provider.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_status\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_status\status_manager;

/**
 * Class provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Method get_metadata.
     *
     * @param collection $collection Parameter collection.
     * @return collection Return value.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table("status_user", [
            "statusid" => "privacy:metadata:status_user:statusid",
            "userid" => "privacy:metadata:status_user:userid",
            "statekey" => "privacy:metadata:status_user:statekey",
            "timecreated" => "privacy:metadata:status_user:timecreated",
            "timemodified" => "privacy:metadata:status_user:timemodified",
        ], "privacy:metadata:status_user");
        return $collection;
    }

    /**
     * Method get_contexts_for_userid.
     *
     * @param int $userid Parameter userid.
     * @return contextlist Return value.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {status_user} su ON su.statusid = cm.instance
                 WHERE su.userid = :userid";
        $params = ["contextlevel" => CONTEXT_MODULE, "modname" => "status", "userid" => $userid];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the users who have data in the supplied context.
     *
     * @param userlist $userlist The user list for the context.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "SELECT su.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {status_user} su ON su.statusid = cm.instance
                 WHERE cm.id = :instanceid";
        $params = [
            "instanceid" => $context->instanceid,
            "modulename" => "status",
        ];
        $userlist->add_from_sql("userid", $sql, $params);
    }
    /**
     * Method export_user_data.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id("status", $context->instanceid);
            if (!$cm) {
                continue;
            }
            $activity = $DB->get_record("status", ["id" => $cm->instance]);
            $response = $DB->get_record("status_user", ["statusid" => $cm->instance, "userid" => $userid]);
            if (!$activity || !$response) {
                continue;
            }
            $states = status_manager::decode_states($activity->states);
            $label = $response->statekey;
            foreach ($states as $state) {
                if ($state["key"] === $response->statekey) {
                    $label = $state["label"];
                    break;
                }
            }
            writer::with_context($context)->export_data([], (object)[
                "status" => $label,
                "timecreated" => transform::datetime($response->timecreated),
                "timemodified" => transform::datetime($response->timemodified),
            ]);
        }
    }

    /**
     * Method delete_data_for_all_users_in_context.
     *
     * @param context $context Parameter context.
     * @return void Return value.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id("status", $context->instanceid);
        if ($cm) {
            $DB->delete_records("status_user", ["statusid" => $cm->instance]);
        }
    }

    /**
     * Delete data for a list of users in a single context.
     *
     * @param approved_userlist $userlist The approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!is_a($context, \context_module::class)) {
            return;
        }

        $cm = get_coursemodule_from_id("status", $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(["statusid" => $cm->instance], $userparams);
        $DB->delete_records_select(
            "status_user",
            "statusid = :statusid AND userid {$userinsql}",
            $params
        );
    }
    /**
     * Method delete_data_for_user.
     *
     * @param approved_contextlist $contextlist Parameter contextlist.
     * @return void Return value.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id("status", $context->instanceid);
            if ($cm) {
                $DB->delete_records("status_user", ["statusid" => $cm->instance, "userid" => $userid]);
            }
        }
    }
}
