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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_notifications_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015062400) {
        // Define table local_notifications to be created.
        $table = new xmldb_table('local_notifications');

        // Adding fields to table local_notifications.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('classname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objectid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('objecttable', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_notifications.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('uniqnote', XMLDB_KEY_UNIQUE, array('classname', 'contextid', 'objectid', 'objecttable'));

        // Conditionally launch create table for local_notifications.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table course_notifications_seen to be renamed to local_notifications_seen.
        $table = new xmldb_table('course_notifications_seen');

        // Launch rename table for course_notifications_seen.
        $dbman->rename_table($table, 'local_notifications_seen');

        // Migrate all data over from course_notifications.
        $newrecords = array();
        $notifications = $DB->get_recordset('course_notifications');
        foreach ($notifications as $notification) {
            $record = new \stdClass();
            switch ($notification->extref) {
                case 'rollover':
                    $record->classname = '\\local_rollover\\notifications\\rollover';
                break;

                case 'manual_classify':
                    $record->classname = '\\local_kent\\notifications\\classify';
                break;

                case 'cla_summary':
                    $record->classname = '\\mod_cla\\notifications\\summary';
                break;

                case 'catman':
                    $record->classname = '\\local_catman\\notifications\\catman';
                break;

                default:
                    debugging("Couldn't identify $notification->extref");
                    $record->classname = $notification->extref;
                break;
            }
            $record->contextid = $notification->contextid;
            $record->objectid = $notification->courseid;
            $record->objecttable = 'course';
            $record->data = trim(substr($notification->message, strpos($notification->message, '</i>') + 4));

            $newrecords[] = $record;
            if (count($newrecords) > 500) {
                $DB->insert_records('local_notifications', $newrecords);
                $newrecords = array();
            }
        }
        $notifications->close();

        if (!empty($newrecords)) {
            $DB->insert_records('local_notifications', $newrecords);
            unset($newrecords);
        }

        // Define table course_notifications to be dropped.
        $table = new xmldb_table('course_notifications');

        // Conditionally launch drop table for course_notifications.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2015062400, 'local', 'notifications');
    }

    if ($oldversion < 2015062401) {
        $table = new xmldb_table('local_notifications');

        // Define field deleted to be added to local_notifications.
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'data');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key uniqnote (unique) to be dropped form local_notifications.
        $key = new xmldb_key('uniqnote', XMLDB_KEY_UNIQUE, array('classname', 'contextid', 'objectid', 'objecttable'));

        // Launch drop key uniqnote.
        $dbman->drop_key($table, $key);

        // Define key uniqnote (unique) to be added to local_notifications.
        $key = new xmldb_key('uniqnote', XMLDB_KEY_UNIQUE, array('classname', 'contextid', 'objectid', 'objecttable', 'deleted'));

        // Launch add key uniqnote.
        $dbman->add_key($table, $key);

        // Notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2015062401, 'local', 'notifications');
    }

    return true;
}