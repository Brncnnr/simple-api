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

namespace local_pimcore\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider
{
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_database_table(
            'local_pimcore_log',
            [
                'userid' => 'privacy:metadata:local_pimcore:userid',
                'pimcoreuuid' => 'privacy:metadata:local_pimcore:pimcoreuuid',
                'username' => 'privacy:metadata:local_pimcore:username',
                'lastname' => 'privacy:metadata:local_pimcore:lastname',
                'firstname' => 'privacy:metadata:local_pimcore:firstname',
            ],
            'privacy:metadata:local_pimcore'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist
    {
        $contextlist = new \core_privacy\local\request\contextlist();
        // Since this system works on a global level (it hooks into the authentication system), the only context is CONTEXT_SYSTEM.
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $contextid = $context->id;

            // add log records

            $sql = 'SELECT * FROM {local_pimcore_log} WHERE userid = :userid';
            $params = ['userid' => $user->id];
            $alldata = [];
            $pimcorelogs = $DB->get_recordset_sql($sql, $params);
            foreach ($pimcorelogs as $pimcorelog) {
                $alldata[$contextid][] = (object) [
                    'userid' => $pimcorelog->userid,
                    'pimcoreuuid' => $pimcorelog->pimcoreuuid,
                    'username' => $pimcorelog->username,
                    'lastname' => $pimcorelog->lastname,
                    'firstname' => $pimcorelog->firstname,
                ];
            }
            $pimcorelogs->close();

            array_walk($alldata, function ($pimcorelog, $contextid) {
                $context = \context::instance_by_id($contextid);
                writer::with_context($context)->export_related_data(
                    ['local_pimcore'],
                    'log',
                    (object) ['pimcorelog' => $pimcorelog]
                );
            });
        }
    }

    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context)
    {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Delete log records.
        $DB->delete_records('local_pimcore_log');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist)
    {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            $user = $contextlist->get_user();

            // Delete log records.
            $DB->delete_records('local_pimcore_log', ['userid' => $user->id]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $userids = $DB->get_fieldset_sql(
            'SELECT DISTINCT userid FROM {tool_usersuspension_log}'
        );
        $userlist->add_users($userids);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('local_pimcore_log', ['userid' => $userid]);
        }
    }
}
