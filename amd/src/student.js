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
 * Student status module.
 *
 * @module    mod_status/student
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification"], function ($, Ajax, Notification) {
    return {
        init: function (cmid) {
            var root = $("[data-region='status-student']");
            root.on("click", "[data-action='set-status']", function () {
                var button = $(this);
                var statekey = button.data("statekey");
                root.find("[data-action='set-status']").prop("disabled", true);
                Ajax.call([{
                    methodname: "mod_status_set_status",
                    args: {cmid: cmid, statekey: statekey}
                }])[0].then(function (result) {
                    root.find("[data-action='set-status']")
                        .removeClass("btn-primary active")
                        .addClass("btn-outline-secondary")
                        .attr("aria-pressed", "false");
                    button.removeClass("btn-outline-secondary")
                        .addClass("btn-primary active")
                        .attr("aria-pressed", "true");
                    var current = root.find("[data-region='current-status']");
                    current.html($("<span>").addClass("mod-status-current-label").append(
                        document.createTextNode(M.util.get_string("yourcurrentstatus", "mod_status") + " "),
                        $("<strong>").attr("data-region", "current-label").text(result.label)
                    ));
                    root.find("[data-region='feedback']").text(M.util.get_string("statussaved", "mod_status"));
                }).catch(Notification.exception).always(function () {
                    root.find("[data-action='set-status']").prop("disabled", false);
                });
            });
        }
    };
});
