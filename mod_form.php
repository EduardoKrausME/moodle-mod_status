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
 * mod_form.php
 *
 * @package   mod_status
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_status\status_manager;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/course/moodleform_mod.php");

/**
 * Class mod_status_mod_form.
 */
class mod_status_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement("text", "name", get_string("statusname", "mod_status"), ["size" => 64]);
        $mform->setType("name", PARAM_TEXT);
        $mform->addRule("name", null, "required", null, "client");
        $mform->addRule("name", get_string("maximumchars", "", 255), "maxlength", 255, "client");

        $this->standard_intro_elements();

        $mform->addElement("html", html_writer::tag("h3", get_string("states", "mod_status")));
        $mform->addElement("textarea", "statuslabels", get_string("states", "mod_status"), [
            "rows" => 7,
            "cols" => 60,
        ]);
        $mform->setType("statuslabels", PARAM_RAW_TRIMMED);
        $mform->setDefault("statuslabels", get_string("defaultstates", "mod_status"));
        $mform->addHelpButton("statuslabels", "states", "mod_status");
        $mform->addRule("statuslabels", null, "required", null, "client");

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if (!empty($defaultvalues["states"])) {
            $states = status_manager::decode_states($defaultvalues["states"]);
            $defaultvalues["statuslabels"] = implode("\n", array_column($states, "label"));
        }
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $labels = status_manager::parse_labels((string)($data["statuslabels"] ?? ""));
        if (count($labels) < 2) {
            $errors["statuslabels"] = get_string("errorminstates", "mod_status");
        } else if (count($labels) > 12) {
            $errors["statuslabels"] = get_string("errormaxstates", "mod_status");
        }
        if (count($labels) !== count(array_unique(array_map(static fn($label) => core_text::strtolower($label), $labels)))) {
            $errors["statuslabels"] = get_string("errorduplicatestates", "mod_status");
        }
        return $errors;
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $mform->addElement("checkbox", "completionstatusset", "", get_string("completionstatusset", "mod_status"));
        return ["completionstatusset"];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data["completionstatusset"]);
    }
}
