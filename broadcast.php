<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('config.php');
date_default_timezone_set(TZ);
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);
$cur_time = date('G:i');
echo "<p><h>Текущее время: $cur_time</h></p><p>TZ = ".TZ."</p>";

if($cur_time == '5:59')
    $conn->query("update students set notificated = FALSE where 1");
$nots_enabled = json_decode(file_get_contents(__DIR__.'/nots.json'), true)['nots'];
$res = $conn->query("select user_id, grup from students where notif = '".$cur_time."' and banned = FALSE and notificated = FALSE");
echo "<p><b>запрос res: select user_id, grup from students where notif = '".$cur_time."' and banned = FALSE and notificated = FALSE </b></p>";
echo "<p>nots = $nots_enabled\n res = ".boolval($res)."\n</p>";
if($nots_enabled and $res)
    while($row = $res->fetch_assoc())
    {
        $gr = $conn->query("select doc_name, banned from groups where name = '".$row['grup']."'")->fetch_assoc();
        if ($gr['banned'] == TRUE)
            continue;
        if (date('w') == 0) {
            $ev_w = !boolval(date('W')%2);
            $wd = 0;
        } else {
            $ev_w = boolval(date('W')%2);
            $wd = date('w');
        }
        global $main_menu_button;
        if(date('w') == 6)
            $message_text .= 'Завтра пар нет';
        else
        {
            $message_text = "Расписание группы ".$row['grup'];
            $message_text .= " на завтра (".($ev_w?'чётная': 'нечётная')." неделя):\n";
            $message_text .= get_schedule($gr['doc_name'], $ev_w, $wd);
        }
        send_message($row['user_id'], $message_text, ['inline' => true,'buttons' => [$main_menu_button]]);
        $conn->query("update students set notificated = TRUE where user_id = ".$row['user_id']);
    }
