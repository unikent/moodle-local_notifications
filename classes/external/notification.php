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

/**
 * Local stuff for Moodle Notifications
 *
 * @package    local_notifications
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifications\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_value;
use external_multiple_structure;
use external_function_parameters;

/**
 * External notification services.
 */
class notification extends external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function dismiss_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(
                PARAM_INT,
                'The notification id',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Dismiss a notification.
     *
     * @param string $id The notification id.
     * @return array[string]
     */
    public static function dismiss($id) {
        $params = self::validate_parameters(self::dismiss_parameters(), array(
            'id' => $id,
        ));

        $notification = \local_notifications\core::get_notification($params['id']);
        return $notification->mark_seen();
    }

    /**
     * Returns nothing.
     *
     * @return external_description
     */
    public static function dismiss_returns() {
        return null;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function expanded_toggle_parameters() {
        return new external_function_parameters(array(
            'id' => new external_value(
                PARAM_INT,
                'The notification id',
                VALUE_REQUIRED
            ),
            'value' => new external_value(
                PARAM_BOOL,
                'The notification expansion value',
                VALUE_REQUIRED
            )
        ));
    }

    /**
     * Toggle expansion of a notification.
     *
     * @param string $id The notification id.
     * @param string $value The notification expansion value.
     * @return array[string]
     */
    public static function expanded_toggle($id, $value) {
        global $USER;

        $params = self::validate_parameters(self::expanded_toggle_parameters(), array(
            'id' => $id,
            'value' => $value,
        ));

        if (isloggedin()) {
            $id = $params['id'];
            check_user_preferences_loaded($USER);
            $key = "notification_{$id}_expanded";
            set_user_preference($key, $params['value']);
        }
    }

    /**
     * Returns nothing.
     *
     * @return external_description
     */
    public static function expanded_toggle_returns() {
        return null;
    }
}
