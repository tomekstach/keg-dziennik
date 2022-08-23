<?php
// This file is part of Moodle Add Users Plugin
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
 * Readme file for local customisations
 *
 * @package    local_addteachers
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->dirroot . '/local/addusers/vendor/llagerlof/moodlerest/MoodleRest.php';

global $USER, $PAGE;

require_login();

if (!isguestuser()) {
    $groupname = optional_param('group', '', PARAM_TEXT);
    $course = optional_param('course', '', PARAM_ALPHANUMEXT);
    $courseID = explode('-', $course)[0];
    $parentID = explode('-', $course)[1];

    if (strlen($groupname) == 0 or $courseID == 0) {
        echo '{"message": "Error: Group or course is empty!", "error": true}';
    } else {
        try {
            $data = new stdClass();
            $data->courseid = $courseID;
            $data->name = $groupname;
            $data->description = '';
            $data->descriptionformat = FORMAT_HTML;

            $newgroupid = groups_create_group($data);

            // Add kegteacher as a member of the new group
            groups_add_member($newgroupid, $USER->id);

            // Assigne group (class) to the grouping (school)
            groups_assign_grouping($parentID, $newgroupid);
            echo '{"message": "Message: Data saved successfully: ' . $newgroupid . '", "error": false}';
        } catch (Exception $th) {
            echo '{"message": "' . $th->getMessage() . '", "error": true}';
        }
    }
}