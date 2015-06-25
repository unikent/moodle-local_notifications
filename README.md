# moodle-local_notifications
Moodle notifications plugin

Example notification

```
class example_notification extends \local_notifications\notification\base {
    /**
     * Returns the component of the notification.
     */
    public function get_component() {
        return 'local_notifications';
    }

    /**
     * Returns the table name the objectid relates to.
     */
    public function get_table() {
        return 'course';
    }

    /**
     * Returns the level of the notification.
     */
    public function get_level() {
        return \local_notifications\notification\base::LEVEL_INFO;
    }

    /**
     * Returns the icon of the notification.
     */
    public function get_icon() {
        return 'fa-heart';
    }

    /**
     * Returns the notification.
     */
    public function render() {
        return 'This is an example!';
    }

    /**
     * Checks custom data.
     */
    public function set_custom_data($data) {
        if (!isset($data['blah'])) {
            throw new \moodle_exception('You must set "blah"!');
        }

        parent::set_custom_data($data);
    }
}
```
