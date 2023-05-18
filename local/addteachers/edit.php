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
 * @copyright  2021+ AstoSoft (https://astosoft.pl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/group/lib.php';

global $USER, $PAGE;

require_login();

if (!isguestuser()) {
    $groups = groups_get_my_groups();

    // TODO: change it to the config field
    $teacherCourses = [
        (object) ['courseID' => 14, 'relatedID' => 13],
        (object) ['courseID' => 16, 'relatedID' => 15],
        (object) ['courseID' => 18, 'relatedID' => 17],
    ];

    // Courses on the test platform
    // $teacherCourses = [
    //     (object) ['courseID' => 11, 'relatedID' => 13],
    //     (object) ['courseID' => 6, 'relatedID' => 12],
    // ];

    $teacher = (object) ["id" => optional_param('id', '', PARAM_INT), 'exists' => false];
    $group = (object) ["id" => optional_param('group', 0, PARAM_INT), 'access' => false];

    $teacher->id = (int) $teacher->id;
    $group->id = (int) $group->id;
    $courseID = 0;
    $courseRelatedID = 0;
    $groupRelatedID = 0;

    if ($group->id == 0) {
        echo '{"message": "Error: Nie wybrano klasy!", "error": true}';
    } else {
        foreach ($groups as $item) {
            if ((int) $item->id === $group->id) {
                $group->access = true;
                $courseID = (int) $item->courseid;

                // Get Course ID for teachers
                foreach ($teacherCourses as $courseObject) {
                    if ($courseObject->courseID === $courseID) {
                        $courseRelatedID = $courseObject->relatedID;
                    }
                }

                // Get Group ID for Course for teachers
                if ($courseRelatedID > 0) {
                    foreach ($groups as $itemGroup) {
                        if ((int) $itemGroup->courseid === $courseRelatedID) {
                            $groupRelatedID = (int) $itemGroup->id;
                        }
                    }
                }
            }
        }

        if ($group->access === false) {
            echo '{"message": "Error: Nie masz uprawnień do tej klasy!", "error": true}';
        } else {
            try {
                groups_add_member((int) $group->id, $teacher->id);
                enrol_try_internal_enrol($courseID, $teacher->id, 4);

                // Add teacher to the Course for teachers
                if ($courseRelatedID > 0 and $groupRelatedID > 0) {
                    enrol_try_internal_enrol($courseRelatedID, $teacher->id, 5);
                    groups_add_member($groupRelatedID, $teacher->id);
                }

                echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
            } catch (Exception $th) {
                echo '{"message": "' . $th->getMessage() . '", "error": true}';
            }
        }
    }
}
