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
    $token = get_config('local_addteachers', 'apitoken');
    $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';
    $MoodleRest = new MoodleRest($baseurl, $token);
    //$MoodleRest->setDebug();

    $groups = groups_get_my_groups();

    $teacher = (object) ["id" => optional_param('id', '', PARAM_INT), 'exists' => false];
    $group = (object) ["id" => optional_param('group', 0, PARAM_INT), 'access' => false];

    if ($group->id == 0) {
        echo '{"message": "Error: Nie wybrano klasy!", "error": true}';
    } else {
        foreach ($groups as $item) {
            if ($item->id == $group->id) {
                $group->access = true;
            }
        }

        if ($group->access === false) {
            echo '{"message": "Error: Nie masz uprawnień do tej klasy!", "error": true}';
        } else {
            if (!empty($tokenobject->error)) {
                echo '{"message": "' . $tokenobject->error . '", "error": false}';
            } else {
                try {
                    $members[] = [
                        'userid' => (int) $teacher->id,
                        'groupid' => (int) $group->id,
                    ];
                    $response = $MoodleRest->request('core_group_add_group_members', array('members' => $members));
                    echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
                } catch (Exception $th) {
                    echo '{"message": "' . $th->getMessage() . '", "error": true}';
                }
            }
        }
    }
}