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
 * Base notification.
 */
abstract class base
{
    const LEVEL_SUCCESS = 'alert-success';
    const LEVEL_INFO = 'alert-info';
    const LEVEL_WARNING = 'alert-warning';
    const LEVEL_DANGER = 'alert-danger';

    protected $id;
    protected $objectid;
    protected $context;
    protected $other;

    /**
     * Creates an instance of the notification.
     */
    public final static function instance($data) {
        $obj = new static();
        $obj->id = $data->id;
        $obj->objectid = $data->objectid;
        $obj->context = $data->context;
        $obj->other = unserialize($data->data);

        return $obj;
    }

    /**
     * Get a copy of this notification.
     */
    public final static function get($objectid, $context) {
        global $DB;

        $obj = $DB->get_record('local_notifications', array(
            'classname' => static::class,
            'objectid' => $objectid,
            'objecttable' => static::get_table(),
            'contextid' => $context->id
        ));

        if (!$obj) {
            return null;
        }

        return static::instance($obj);
    }

    /**
     * Save the notification to DB.
     */
    public final static function create($data, $update = true) {
        global $DB;

        if (!isset($data->objectid)) {
            throw new \coding_exception("You must set an objectid.");
        }

        if (!isset($data->context) || !is_object($data->context)) {
            throw new \coding_exception("You must set a context.");
        }

        $obj = new static();
        if (isset($data['other'])) {
            $obj->set_custom_data($data['other']);
        }

        $record = new \stdClass();
        $record->classname = static::class;
        $record->contextid = $data->context->id;
        $record->objectid = $data->objectid;
        $record->objecttable = static::get_table();

        // Check for existing record.
        $existing = $DB->get_record('local_notifications', $record);

        $record->data = serialize($obj->other);

        // Update existing record.
        if ($existing) {
            $existing->data = $record->data;
            $DB->update_record('local_notifications', $existing);
            return $existing;
        }

        // Create a new record.
        $record->id = $DB->insert_record('local_notifications', $record);

        // Create the event.
        $obj = static::instance($record);
        $event = \local_notifications\event\notification_created::create($obj->get_event_data());
        $event->trigger();

        return $obj;
    }

    /**
     * Is this notification visible for the current user?
     */
    public function is_visible($userid = null) {
        global $DB, $USER;

        $userid = empty($userid) ? $USER->id : $userid;

        if (!$this->is_dismissble()) {
            return true;
        }

        // Check!
        return !$DB->record_exists('local_notifications_seen', array(
            'nid' => $this->id,
            'userid' => $userid
        ));
    }

    /**
     * Returns the id.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the related object.
     */
    public function get_object() {
        global $DB;

        return $DB->get_record(static::get_table(), array(
            'id' => $this->objectid
        ));
    }

    /**
     * Returns the context.
     */
    public function get_context() {
        if (empty($this->context)) {
            return null;
        }

        if (!is_object($this->context)) {
            $this->context = \context::instance_by_id($this->context);
        }

        return $this->context;
    }

    /**
     * Returns event data.
     */
    private function get_event_data() {
        // Create the event.
        $event = array(
            'objectid' => $this->id,
            'context' => $this->get_context()
        );

        $table = static::get_table();
        if ($table == 'course') {
            $event['courseid'] = $this->objectid;
        }

        if ($table == 'user') {
            $event['relateduserid'] = $this->objectid;
        }

        return $event;
    }

    /**
     * Delete the notification.
     */
    public function delete() {
        global $DB;

        if (!isset($this->id)) {
            throw new \coding_exception("Cannot delete a notification without an ID.");
        }

        $DB->update_record('local_notifications', array(
            'id' => $this->id,
            'deleted' => 1
        ));

        $event = \local_notifications\event\notification_deleted::create($this->get_event_data());
        $event->trigger();
    }

    /**
     * Mark this as seen.
     */
    public function mark_seen($userid = null) {
        global $DB, $USER;

        if (!isset($this->id)) {
            throw new \coding_exception("Cannot mark a notification without an ID.");
        }

        $userid = $userid ? $userid : $USER->id;

        $DB->insert_record('local_notifications_seen', array(
            'nid' => $this->id,
            'userid' => $userid
        ));

        $event = \local_notifications\event\notification_seen::create(array(
            'objectid' => $this->id,
            'context' => \context_user::instance($userid),
            'relateduserid' => $userid
        ));
        $event->trigger();
    }

    /**
     * Returns the number of "actions" of the notification.
     */
    public function get_actions() {
        return 0;
    }

    /**
     * Is the action dismissable?
     */
    public function is_dismissble() {
        return false;
    }

    /**
     * Checks custom data.
     */
    public function set_custom_data($data) {
        $this->other = (array)$data;
    }

    /**
     * Returns the icon of the notification.
     */
    public function get_icon() {
        switch ($this->get_level()) {
            case self::LEVEL_SUCCESS:
            return 'fa-check';

            case self::LEVEL_INFO:
            return 'fa-info-circle';

            case self::LEVEL_WARNING:
            return 'fa-exclamation-triangle';

            case self::LEVEL_DANGER:
            return 'fa-times-circle';
        }
    }

    /**
     * Returns the component of the notification.
     */
    public abstract static function get_component();

    /**
     * Returns the table name the objectid relates to.
     */
    public abstract static function get_table();

    /**
     * Returns the level of the notification.
     */
    public abstract function get_level();

    /**
     * Returns the notification.
     */
    public abstract function render();
}
