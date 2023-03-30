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
 * @package    local_addcoordinator
 * @copyright  2021 AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/user/lib.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->libdir . '/adminlib.php';

global $USER, $PAGE, $DB;

require_login();

if (!function_exists('clearString')) {
    function clearString($string)
    {
        return addslashes(stripslashes(strip_tags(trim($string))));
    }
}

if (!isguestuser()) {
    $userid = $USER->id; // Owner of the page
    $coordinatorID = (int) optional_param('id', '', PARAM_INT);
    $schoolName = clearString(optional_param('school', '', PARAM_TEXT));
    $course = (int) optional_param('course', '', PARAM_INT);
    $className = clearString(optional_param('group', '', PARAM_TEXT));

    if ($course === 0) {
        echo '{"message": "Message: Proszę wybrać kurs!", "error": true}';
    } elseif (strlen($className) < 2) {
        echo '{"message": "Message: Proszę podać poprawną nazwę klasy!", "error": true}';
    } else {
        try {
            // Check if school exists in the course
            if (groups_get_grouping_by_name($course, $schoolName) !== false) {
                throw new Exception('Podana szkoła już istnieje w wybranym kursie!');
            }

            // Add group in the selected course for the school (main group - groups_create_grouping($data, $editoroptions=null))
            $schoolData = (object) [
                'name' => clearString($schoolName),
                'courseid' => $course,
            ];
            $schoolID = groups_create_grouping($schoolData);

            // Add group in the selected course for the class (standard group - groups_create_group($data, $editform=false, $editoroptions=null)) {
            $classData = (object) [
                'name' => $className,
                'courseid' => $course,
            ];

            $classID = groups_create_group($classData);

            // Assigne class group to the school one - groups_assign_grouping($groupingid, $groupid)
            groups_assign_grouping($schoolID, $classID);

            enrol_try_internal_enrol($course, $coordinatorID, 9);

            groups_add_member($classID, $coordinatorID);
        } catch (Exception $th) {
            echo '{"message": "' . $th->getMessage() . '", "error": true}';
            exit();
        }

        echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
    }
}
