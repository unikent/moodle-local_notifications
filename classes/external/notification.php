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
     * Expose to AJAX
     * @return boolean
     */
    public static function dismiss_is_allowed_from_ajax() {
        return true;
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
}
