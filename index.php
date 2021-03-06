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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('notificationsmanager');

$params = array(
    'page'    => optional_param('page', 0, PARAM_INT),
    'perpage' => optional_param('perpage', 25, PARAM_INT),
    'classname' => optional_param('classname', '', PARAM_RAW),
    'objectid' => optional_param('objectid', '', PARAM_INT)
);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new \moodle_url('/local/notifications/index.php', $params));
$PAGE->set_title("Notifications Center");

$action = optional_param('action', '', PARAM_RAW);
if ($action == 'delete') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);
    $notification = \local_notifications\core::get_notification($id);
    $notification->delete();
    redirect($PAGE->url, 'Notification deleted', 2);
}

// Check the form.
$form = new \local_notifications\form\arbitrary();
if ($data = $form->get_data()) {
    \local_notifications\notification\arbitrary::create(array(
        'objectid' => $data->courseid,
        'context' => \context_course::instance($data->courseid),
        'other' => array(
            'actions' => $data->actions,
            'level' => $data->type,
            'dismissable' => $data->dismissable ? true : false,
            'message' => $data->message
        )
    ));

    redirect($PAGE->url, 'Notification created', 2);
}

$PAGE->requires->js_call_amd('local_notifications/filtering', 'init', array());

$table = new \html_table();
$table->head = array(
    'Course', 'Classname', 'Data', 'Action'
);
$table->data = array();

$notificationparams = array(
    'objecttable' => 'course',
    'deleted' => 0
);

if (!empty($params['classname'])) {
    $notificationparams['classname'] = $params['classname'];
}

if (!empty($params['objectid'])) {
    $notificationparams['objectid'] = $params['objectid'];
}

$courses = $DB->get_records('course', null, 'shortname');
$notifications = $DB->get_recordset('local_notifications', $notificationparams, '', '*', $params['page'] * $params['perpage'], $params['perpage']);
foreach ($notifications as $row) {
    $course = \html_writer::tag('a', $courses[$row->objectid]->shortname, array(
        'href' => new \moodle_url('/course/view.php', array(
            'id' => $row->objectid
        )),
        'target' => '_blank'
    ));

    $actionurl = new \moodle_url('/local/notifications/index.php', array_merge($params, array(
        'sesskey' => sesskey(),
        'action' => 'delete',
        'id' => $row->id
    )));
    $action = \html_writer::tag('button', 'Delete', array(
        'href' => $actionurl,
        'class' => 'btn btn-danger btn-sm'
    ));

    $data = json_encode(unserialize($row->data), JSON_PRETTY_PRINT);

    $databutton = \html_writer::tag('button', 'View data', array(
        'class' => 'btn btn-primary btn-sm',
        'data-toggle' => 'modal',
        'data-target' => "#datamodal{$row->id}"
    ));
    $databutton .= <<<HTML5
        <div class="modal fade" id="datamodal{$row->id}" tabindex="-1" role="dialog" aria-labelledby="datamodallabel{$row->id}">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="datamodallabel{$row->id}">Notification Data</h4>
                    </div>
                    <div class="modal-body">
                        <code class="json">{$data}</code>
                    </div>
                </div>
            </div>
        </div>
HTML5;

    $table->data[] = array(
        new \html_table_cell($course),
        $row->classname,
        new \html_table_cell(empty($data) || $data == 'null' ? '' : $databutton),
        new \html_table_cell($action)
    );
}

$notifications->close();

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading("Notifications Center");

// Display filtering options.
$extrefs = $DB->get_fieldset_sql('SELECT DISTINCT classname FROM {local_notifications}');
$prettyextrefs = array();
foreach ($extrefs as $classname) {
    $prettyextrefs[$classname] = $classname;
}
echo \html_writer::select($prettyextrefs, 'classname', $params['classname'], array('' => 'Filter by classname'));

$prettycourses = array();
foreach ($courses as $course) {
    $prettycourses[$course->id] = $course->shortname . ': ' . $course->fullname;
}
echo \html_writer::select($prettycourses, 'course', $params['objectid'], array('' => 'Filter by course'));

echo '<br />';

// Display notifications table.
echo \html_writer::div(\html_writer::table($table), 'no-overflow');

$total = $DB->count_records('local_notifications', $notificationparams);
echo $OUTPUT->paging_bar($total, $params['page'], $params['perpage'], $PAGE->url);

// Add form.
$form->display();

// Output footer.
echo $OUTPUT->footer();
