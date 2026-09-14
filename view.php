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
 * view.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$cm = get_coursemodule_from_id("status", $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$status = $DB->get_record("status", ["id" => $cm->instance], "*", MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability("mod/status:view", $context);

$PAGE->set_url("/mod/status/view.php", ["id" => $cm->id]);
$PAGE->set_title(format_string($status->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($status);

\mod_status\event\course_module_viewed::create([
    "objectid" => $status->id,
    "context" => $context,
])->trigger();

$manager = new \mod_status\status_manager($status, $cm, $context);
$PAGE->requires->strings_for_js(["yourcurrentstatus", "statussaved"], "mod_status");

if (has_capability("mod/status:viewdashboard", $context)) {
    $PAGE->requires->js_call_amd("mod_status/dashboard", "init", [$cm->id, 5000]);
    $templatedata = $manager->get_dashboard_template_data();
    $template = "mod_status/teacher";
} else {
    require_capability("mod/status:setstatus", $context);
    $PAGE->requires->js_call_amd("mod_status/student", "init", [$cm->id]);
    $templatedata = $manager->get_student_template_data($USER->id);
    $template = "mod_status/student";
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($status->name));
if (!empty($status->intro)) {
    echo $OUTPUT->box(format_module_intro("status", $status, $cm->id), "generalbox mod_introbox", "statusintro");
}
echo $OUTPUT->render_from_template($template, $templatedata);
echo $OUTPUT->footer();
