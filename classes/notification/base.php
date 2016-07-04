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

namespace local_notifications\notification;

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
     * @param $data
     * @return static
     */
    public final static function instance($data) {
        $obj = new static();
        $obj->id = $data->id;
        $obj->objectid = $data->objectid;
        $obj->context = $data->contextid;
        $obj->other = unserialize($data->data);

        return $obj;
    }

    /**
     * Get a copy of this notification.
     * @param $objectid
     * @param $context
     * @return base|null
     * @throws \coding_exception
     */
    public final static function get($objectid, $context) {
        global $DB;

        $ctx = is_object($context) ? $context->id : $context;

        $obj = $DB->get_record('local_notifications', array(
            'classname' => static::class,
            'objectid' => $objectid,
            'objecttable' => static::get_table(),
            'contextid' => $ctx
        ));

        if (!$obj) {
            return null;
        }

        return static::instance($obj);
    }

    /**
     * Save the notification to DB.
     * @param $data
     * @param bool $update
     * @return base|static
     * @throws \coding_exception
     */
    public final static function create($data, $update = true) {
        global $DB;

        $data = (object)$data;

        if (!isset($data->objectid)) {
            throw new \coding_exception("You must set an objectid.");
        }

        if (!isset($data->context)) {
            throw new \coding_exception("You must set a context.");
        }

        if (!is_object($data->context)) {
            $data->context = \context::instance_by_id($data->context);
        }

        $obj = new static();
        if (isset($data->other)) {
            $obj->set_custom_data($data->other);
        }

        $record = new \stdClass();
        $record->classname = static::class;
        $record->contextid = $data->context->id;
        $record->objectid = $data->objectid;
        $record->objecttable = static::get_table();

        // Check for existing record.
        $existing = $DB->get_record('local_notifications', (array)$record);

        $record->data = serialize($obj->other);

        // Update existing record.
        if ($existing && $update) {
            $existing->deleted = '0';
            $existing->data = $record->data;
            $DB->update_record('local_notifications', $existing);
            return static::instance($existing);
        }

        // Create a new record.
        $record->id = $DB->insert_record('local_notifications', (array)$record);

        // Create the event.
        $obj = static::instance($record);
        $event = \local_notifications\event\notification_created::create($obj->get_event_data());
        $event->trigger();

        return $obj;
    }

    /**
     * Save changes.
     */
    public function save() {
        static::create($this, true);
    }

    /**
     * Is this notification visible for the current user?
     * @param null $userid
     * @return bool
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

        $DB->update_record('local_notifications', (object)array(
            'id' => $this->id,
            'deleted' => 1
        ));

        $event = \local_notifications\event\notification_deleted::create($this->get_event_data());
        $event->trigger();
    }

    /**
     * Purge a notification (Use delete instead).
     */
    public function purge() {
        global $DB;

        if (!isset($this->id)) {
            throw new \coding_exception("Cannot delete a notification without an ID.");
        }

        $DB->delete_records('local_notifications', array(
            'id' => $this->id
        ));
    }

    /**
     * Mark this as seen.
     * @param null $userid
     * @throws \coding_exception
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
     * @param $data
     */
    protected function set_custom_data($data) {
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

        return 'fa-question';
    }

    /**
     * Returns any action buttons associated with this notification.
     */
    protected function get_action_icons() {
        $actions = '';

        if ($this->is_dismissble()) {
            $id = $this->get_id();
            $actions .= <<<HTML5
            <button type="button" class="close cnid-dismiss" data-dismiss="alert" data-id="{$id}" aria-label="Close">
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
HTML5;
        }

        return $actions;
    }

    /**
     * Performs a full render.
     */
    public final function render() {
        if (!$this->is_visible()) {
            return '';
        }

        $message = $this->get_contents();
        if (empty($message)) {
            return '';
        }

        $classes = "alert " . $this->get_level();
        if ($this->is_dismissble()) {
            $classes .= ' alert-dismissible';
        }

        // Grab any other actions.
        $actions = \html_writer::div($this->get_action_icons(), 'action-icons');

        // Render the icon.
        $icon = $this->get_icon();
        if ($icon) {
            $classes .= ' alert-icon';
            $icon = \html_writer::tag('i', '', array(
                'class' => 'fa ' . $icon
            ));
        }

        return <<<HTML5
        <div class="{$classes}" role="alert">
            {$actions}
            {$icon} {$message}
        </div>
HTML5;
    }

    /**
     * Returns the component of the notification.
     */
    public static function get_component() {
        throw new \coding_exception("Invalid notification: get_component not implemented.");
    }

    /**
     * Returns the table name the objectid relates to.
     */
    public static function get_table() {
        throw new \coding_exception("Invalid notification: get_table not implemented.");
    }

    /**
     * Returns the level of the notification.
     */
    public abstract function get_level();

    /**
     * Returns the notification.
     */
    protected abstract function get_contents();
}
