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
// require_once $CFG->dirroot . '/group/lib.php';
// require_once $CFG->dirroot . '/local/addusers/vendor/llagerlof/moodlerest/MoodleRest.php';\
require_once $CFG->dirroot . '/user/lib.php';

global $USER, $PAGE, $DB;

require_login();

if (!isguestuser()) {
    // $token = get_config('local_addcoordinator', 'apitoken');
    // $baseurl = $CFG->wwwroot . '/webservice/rest/server.php';
    // $MoodleRest = new MoodleRest($baseurl, $tokenobject->token);
    //$MoodleRest->setDebug();

    // $groups = groups_get_my_groups();

    $userid = optional_param('id', '', PARAM_INT);
    $userObj = $DB->get_record('user', array('id' => $userid));

    // $coordinator = (object) ["userid" => optional_param('id', '', PARAM_INT), 'exists' => false];
    $userObj->firstname = (string) optional_param('firstname', 0, PARAM_TEXT);
    $userObj->lastname = (string) optional_param('lastname', 0, PARAM_TEXT);
    $userObj->email = (string) optional_param('email', 0, PARAM_EMAIL);
    $password = (string) optional_param('password', 0, PARAM_TEXT);

    // print_r($userObj);

    $errmsg = '';

    if (strlen($userObj->firstname) < 2) {
        $errmsg = '{"message": "Error: Zła wartość w polu imię!", "error": true}';
    }

    if (strlen($userObj->lastname) < 2) {
        $errmsg = '{"message": "Error: Zła wartość w polu nazwisko!", "error": true}';
    }

    if (!filter_var($userObj->email, FILTER_VALIDATE_EMAIL)) {
        $errmsg = '{"message": "Error: Zła wartość w polu email!", "error": true}';
    }

    if (!check_consecutive_identical_characters($password, $CFG->maxconsecutiveidentchars)) {
        $errmsg = '{"message":"' . get_string('errormaxconsecutiveidentchars', 'auth', $CFG->maxconsecutiveidentchars) . '", "error": true}';
    }

    if (strlen($password) > 1 and $errmsg === '') {
        if (!empty($CFG->additional_password_policy_checks_function)) {
            call_user_func_array($CFG->additional_password_policy_checks_function,
                array($password, &$errmsg));
        }

        if ($errmsg !== '') {
            $errmsg = '{"message":"' . (string) $errmsg . '", "error": true}';
        }
    }

    if ($errmsg === '') {
        // Update user with new profile data.
        user_update_user($userObj, false);

        echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
    } else {
        echo $errmsg;
    }

    // if ($group->id == 0) {
    //     echo '{"message": "Error: Nie wybrano klasy!", "error": true}';
    // } else {
    //     foreach ($groups as $item) {
    //         if ($item->id == $group->id) {
    //             $group->access = true;
    //         }
    //     }

    //     if ($group->access === false) {
    //         echo '{"message": "Error: Nie masz uprawnień do tej klasy!", "error": true}';
    //     } else {
    //         if (!empty($tokenobject->error)) {
    //             echo '{"message": "' . $tokenobject->error . '", "error": false}';
    //         } else {
    //             try {
    //                 $members[] = [
    //                     'userid' => (int) $coordinator->id,
    //                     'groupid' => (int) $group->id,
    //                 ];
    //                 $response = $MoodleRest->request('core_group_add_group_members', array('members' => $members));
    //                 echo '{"message": "Message: Dane zostały zapisane poprawnie!", "error": false}';
    //             } catch (Exception $th) {
    //                 echo '{"message": "' . $th->getMessage() . '", "error": true}';
    //             }
    //         }
    //     }
    // }
}