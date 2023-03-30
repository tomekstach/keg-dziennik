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

global $USER, $PAGE;

require_login();

if (!isguestuser()) {
    $groups = groups_get_my_groups();

    $group = (object) ['id' => (int) optional_param('id', '', PARAM_ALPHANUMEXT), 'access' => false];

    foreach ($groups as $item) {
        if ($item->id == $group->id) {
            $group->access = true;
        }
    }

    if ($group->access === false) {
        echo '{"message": "Error: You do not have access to this group!", "error": true}';
    } else {
        groups_delete_group($group->id);
    }
}
