<?php
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
 * @package   local_pimcore
 * @copyright 2022, Alex Süß <alexander.suess@kamedia.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pimcore\task;

class suspend_delete extends \core\task\scheduled_task
{
    private $usersuspended = 0;
    private $userdeleted = 0;

    public function get_name()
    {
        return get_string('suspend_delete', 'local_pimcore');
    }

    public function execute()
    {
        /**
         * * get user
         */
        global $DB, $CFG;

        require_once $CFG->dirroot . '/user/profile/lib.php';
        require_once $CFG->dirroot . '/user/lib.php';

        $users = $DB->get_records('user', ['deleted' => 0]);
        /** * suspend */
        if ($users) {
            foreach ($users as $user) {
                $currenttime = time();
                $user = \core_user::get_user($user->id);
                profile_load_data($user);
                $pimcoresuspend = intval($user->profile_field_pimcore_suspend);
                // time frame to be suspended
                // $timespan = 31104000; // 12
                $timespan = 15552000;  // 6 months
                $pimcoredelete = $pimcoresuspend + $timespan;
                if (
                    isset($user->profile_field_pimcore_suspend) &&
                    !empty($user->profile_field_pimcore_suspend) &&
                    $user->profile_field_pimcore_suspend != 0 &&
                    $pimcoresuspend < $currenttime &&
                    $user->suspended == 0
                ) {
                    ++$this->usersuspended;

                    $user->profile_field_pimcore_delete = $pimcoredelete;
                    profile_save_data($user);

                    $user->suspended = 1;
                    // Force logout.
                    \core\session\manager::kill_user_sessions($user->id);
                    user_update_user($user, false, false);
                }
            }
            mtrace('User suspended ' . $this->usersuspended . ' user');
        } else {
            mtrace('No user suspended.');
        }

        /** * delete */
        if ($users) {
            foreach ($users as $user) {
                $currenttime = time();

                $user = \core_user::get_user($user->id);
                profile_load_data($user);
                $pimcoredelete = $user->profile_field_pimcore_delete;

                if (
                    isset($pimcoredelete) &&
                    !empty($pimcoredelete) &&
                    $pimcoredelete != 0 &&
                    $pimcoredelete < $currenttime &&
                    $user->suspended == 1 &&
                    $user->deleted == 0
                ) {
                    $user->deleted = 1;

                    // Force logout.
                    \core\session\manager::kill_user_sessions($user->id);
                    user_update_user($user, false, false);
                    ++$this->userdeleted;
                }
            }
            mtrace('User deleted: ' . $this->userdeleted . ' user');
        } else {
            mtrace('No user deleted.');
        }
    }
}
