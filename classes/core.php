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

namespace local_notifications;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification center
 */
class core
{
    /**
     * Returns the notifications for a course.
     */
    public static function get_notification($notification) {
        global $DB;

        if (!is_object($notification)) {
            $notification = $DB->get_record('local_notifications', array(
                'id' => $notification
            ));
        }

        $classname = "\\" . $notification->classname;
        $notification->context = \context::instance_by_id($notification->contextid);

        if (!class_exists($classname)) {
            debugging("$classname does not exist.");
            return null;
        }

        return $classname::instance($notification);
    }

    /**
     * Returns the notifications for a course.
     */
    public static function get_notifications($courseid) {
        global $DB;

        $courseid = is_object($courseid) ? $courseid->id : $courseid;

        $records = $DB->get_records('local_notifications', array(
            'objectid' => $courseid,
            'objecttable' => 'course',
            'deleted' => 0
        ));

        $notifications = array();
        foreach ($records as $record) {
            $notification = static::get_notification($record);
            if ($notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * Returns the notification count for a course.
     */
    public static function count_notifications($courseid) {
        global $DB;

        $courseid = is_object($courseid) ? $courseid->id : $courseid;

        return $DB->count_records('local_notifications', array(
            'objectid' => $courseid,
            'objecttable' => 'course',
            'deleted' => 0
        ));
    }

    /**
     * Returns the action count for a course.
     */
    public static function count_actions($courseid) {
        $notifications = static::get_notifications($courseid);

        $total = 0;
        foreach ($notifications as $notification) {
            $total += $notification->get_actions();
        }

        return $total;
    }
}
