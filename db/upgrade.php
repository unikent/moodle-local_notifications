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

        // Clear out course_notifications_seen.
        $DB->delete_records('course_notifications_seen');

        // Define table course_notifications_seen to be renamed to local_notifications_seen.
        $table = new xmldb_table('course_notifications_seen');

        // Launch rename table for course_notifications_seen.
        $dbman->rename_table($table, 'local_notifications_seen');

        // Define table course_notifications to be dropped.
        $table = new xmldb_table('course_notifications');

        // Conditionally launch drop table for course_notifications.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2015062400, 'local', 'notifications');
    }

    if ($oldversion < 2015062500) {
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
        upgrade_plugin_savepoint(true, 2015062500, 'local', 'notifications');
    }

    // Upgrade notifications.
    if ($oldversion < 2015062501) {
        require_once($CFG->dirroot . "/mod/cla/lib.php");

        // Get a list of courses with a CLA notification.
        $courses = $DB->get_records_sql('SELECT course as id FROM {cla} WHERE rolled_over_inactive=2 AND deleted=0 GROUP BY course');
        foreach ($courses as $course) {
            cla_generate_course_notification($course->id);
        }

        // Cla savepoint reached.
        upgrade_mod_savepoint(true, 2015062501, 'local', 'notifications');
    }

    if ($oldversion < 2015062502) {
        $expirations = $DB->get_records('catman_expirations');
        foreach ($expirations as $expiration) {
            $context = \context_course::instance($expiration->courseid);

            \local_catman\notification\scheduled::create(array(
                'objectid' => $expiration->courseid,
                'context' => $context,
                'other' => array(
                    'expirationtime' => $expiration->expiration_time
                )
            ));
        }

        upgrade_plugin_savepoint(true, 2015062502, 'local', 'notifications');
    }

    // Upgrade notifications.
    if ($oldversion < 2015062503) {
        $contextpreload = \context_helper::get_preload_record_columns_sql('x');
        $courses = $DB->get_records_sql("SELECT c.id, c.shortname, $contextpreload
            FROM {course} c
            INNER JOIN {context} x ON (c.id=x.instanceid AND x.contextlevel=".CONTEXT_COURSE.")
        ");
        foreach ($courses as $course) {
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);

            if (strpos($course->shortname, 'DX') === 0) {
                \local_kent\notification\classify::create(array(
                    'objectid' => $course->id,
                    'context' => $context
                ));
            }

            // Regenerate the deprecated notification.
            $task = new \local_kent\task\generate_deprecated_notification();
            $task->set_custom_data(array(
                'courseid' => $course->id
            ));
            \core\task\manager::queue_adhoc_task($task);
        }

        // Kent savepoint reached.
        upgrade_plugin_savepoint(true, 2015062503, 'local', 'notifications');
    }

    if ($oldversion < 2015062504) {
        // Upgrade previous notifications.
        $courses = $DB->get_records('course');
        foreach ($courses as $course) {
            $context = \context_course::instance($course->id);

            $kr = new \local_rollover\Course($course);
            if ($kr->get_status() !== \local_rollover\Rollover::STATUS_NONE) {
                $record = $kr->get_rollover();

                $kc = new \local_kent\Course($course->id);

                // Generate a status notification.
                \local_rollover\notification\status::create(array(
                    'objectid' => $course->id,
                    'context' => $context,
                    'other' => array(
                        'complete' => true,
                        'rolloverid' => $record->id,
                        'record' => $record,
                        'manual' => $kc->is_manual()
                    )
                ));
            }
        }

        upgrade_plugin_savepoint(true, 2015062504, 'local', 'notifications');
    }

    return true;
}