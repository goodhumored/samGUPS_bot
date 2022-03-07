<?php
require_once('config.php');
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$update = json_decode(file_get_contents('php://input'), true);
if ($update['secret'] != SECRET_KEY)
    exit;

if($update['type'] == 'message_new')
{
    $message = $update['object']['message'];
    $peer_id = $message['from_id'];
    $admin = false;
    $kirill = false;
    if ($peer_id == ADMIN_ID)
        $admin = true;
    if ($peer_id == KIRILL_ID)
        $kirill = true;
    if($message['text'] == 'Стоп' and ($admin or $kirill))
    {
        send_message($peer_id, 'Вырубаюсь');
        echo 'ok';
        exit;
    }
    if($conn->query("select banned from students where user_id = ".$peer_id)->fetch_assoc()['banned'])
    {
        echo 'ok';
        exit;
    }
    // Проверяем, писал ли пользователь боту до этого
    if ($student = $conn->query("select * from students where user_id = ".$peer_id)->fetch_assoc()){
        //var_dump($student);
        // Проверяем, "зарегистрирован" ли пользователь в боте
        if($student['registered'])
        {
            if ($message['text'] == 'Возврат в главное меню')
                go_menu($peer_id, 'main');
            else
                switch($student['menu'])
                {
                    case 'main':
                        switch($message['text'])
                        {
                            case 1: // Меню с расписанием
                                go_menu($peer_id, 'schedule');
                                break;
                            case 2:  // Меню уведомлений
                                go_menu($peer_id, 'notifications');
                                break;
                            case 3:  // Меню с расписанием других групп
                                go_menu($peer_id, 'other_schedule');
                                break;
                            case 4:  // Меню с расписанием преподавателей
                                go_menu($peer_id, 'teachers_schedule');
                                break;
                            case 5:  // Назад к выбору группы
                                global $cancel_button;
                                $conn->query("update students set registered = FALSE, grup = '' where user_id = $peer_id");
                                $res = $conn->query("select name from groups where faculty = '".$student['faculty']."' and course = '".$student['course']." and banned = 0'");
                                $groups = [];
                                while ($gr = $res->fetch_assoc())
                                    array_push($groups, $gr['name']);
                                $buttons = array_to_buttons($groups, 3);
                                array_push($buttons, $cancel_button);
                                $keyboard =
                                ['inline' => true,'buttons' => $buttons];
                                send_message($peer_id, 'В какой ты группе?', $keyboard);
                                break;
                            case 6:  // Удалить все данные
                                go_menu($peer_id, 'delete_confirm');
                                break;
                            case 'Меню администратора':
                                if($admin or $kirill)
                                    go_menu($peer_id, 'admin');
                                break;
                            default:
                                global $default_message, $default_keyboard, $admin_button;
                                $menu_keyboard = $default_keyboard;
                                if ($admin or $kirill)
                                    array_push($menu_keyboard['buttons'], $admin_button);
                                send_message($peer_id, $default_message, $menu_keyboard);
                        }
                        break;
                    case 'schedule':
                        global $main_menu_button, $cancel_button;
                        switch($message['text'])
                        {
                            case 1: // Расписание на сегодня
                                if (date('w') == 0) {
                                    $ev_w = boolval(date('W')%2);
                                    $wd = 7;
                                } else {
                                    $ev_w = boolval(date('W')%2);
                                    $wd = date('w')-1;
                                }
                                $gr = $conn->query("select banned, doc_name from groups where name='".$student['grup']."'")->fetch_assoc();
                                if($gr['banned'])
                                    send_message($peer_id, 'Расписание этой группы удалено администратором');
                                else {
                                    $message_text = "Расписание группы: ".$student['grup'];
                                    $message_text .= " на сегодня (".($ev_w?'чётная': 'нечётная')." неделя):\n";
                                    if(date('w') == 0)
                                        $message_text .= 'Сегодня пар нет';
                                    else
                                    {
                                        $sch = get_schedule($gr['doc_name'], $ev_w, $wd);
                                        if ($sch === FALSE)
                                            $message_text .= 'Сегодня пар нет';
                                        else
                                            $message_text .= $sch;
                                    }
                                    $menu_keyboard = ['inline' => true,'buttons' => [$cancel_button, $main_menu_button]];
                                    send_message($peer_id, $message_text, $menu_keyboard);
                                }
                                break;
                            case 2: // Расписание на завтра
                                if (date('w') == 0) {
                                    $ev_w = !boolval(date('W')%2);
                                    $wd = 0;
                                } else {
                                    $ev_w = boolval(date('W')%2);
                                    $wd = date('w');
                                }
                                $gr = $conn->query("select banned, doc_name from groups where name='".$student['grup']."'")->fetch_assoc();
                                if($gr['banned'])
                                    send_message($peer_id, 'Расписание этой группы удалено администратором');
                                else {
                                    $message_text = "Расписание группы: ".$student['grup'];
                                    $message_text .= " на завтра (".($ev_w?'чётная': 'нечётная')." неделя):\n";
                                    if(date('w') == 6)
                                        $message_text .= 'Завтра пар нет';
                                    else
                                    {
                                        $sch = get_schedule($gr['doc_name'], $ev_w, $wd);
                                        if ($sch === FALSE)
                                            $message_text .= 'Завтра пар нет';
                                        else
                                            $message_text .= $sch;
                                    }
                                    $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button, $main_menu_button]];
                                    send_message($peer_id, $message_text, $menu_keyboard);
                                }
                                break;
                            case 3: // Расписание на неделю
                                $ev_w = boolval(date('W')%2);
                                $gr = $conn->query("select banned, doc_name from groups where name='".$student['grup']."'")->fetch_assoc();
                                if($gr['banned'])
                                    send_message($peer_id, 'Расписание этой группы удалено администратором');
                                else {
                                    $message_text = "Расписание группы: ".$student['grup']." на эту неделю\n";
                                    $message_text .= "Неделя".($ev_w?' чётная': ' нечётная').', '.get_week_str(date('d-m-Y')).".\n";
                                    $message_text .= get_schedule($gr['doc_name'], $ev_w);
                                    $menu_keyboard = ['inline' => true,'buttons' => [$cancel_button, $main_menu_button]];
                                    send_message($peer_id, $message_text, $menu_keyboard);
                                }
                                break;
                            case 4: // Расписание на след. неделю
                                $ev_w = !boolval(date('W')%2);
                                $gr = $conn->query("select banned, doc_name from groups where name='".$student['grup']."'")->fetch_assoc();
                                if($gr['banned'])
                                    send_message($peer_id, 'Расписание этой группы удалено администратором');
                                else {
                                    $message_text = "Расписание группы: ".$student['grup']." на следующую неделю\n";
                                    $date = date('d-m-Y', strtotime('+1 week', strtotime(date('d-m-Y'))));
                                    $message_text .= "Неделя".($ev_w?' чётная': ' нечётная').', '.get_week_str($date).".\n";
                                    $message_text .= get_schedule($gr['doc_name'], $ev_w);
                                    $menu_keyboard = ['inline' => true,'buttons' => [$cancel_button, $main_menu_button]];
                                    send_message($peer_id, $message_text, $menu_keyboard);
                                }
                                break;
                            case 5: // В главное меню
                                go_menu($peer_id, 'main');
                                break;
                            case "Возврат в главное меню": // В главное меню
                                go_menu($peer_id, 'main');
                                break;
                            default:
                                global $schedule_menu_message, $schedule_menu_keyboard;
                                send_message($peer_id, $schedule_menu_message, $schedule_menu_keyboard);
                        }
                        break;
                    case 'notifications':
                        global $notifications_greeding_message, $notification_menu_message, $notification_menu_keyboard, $main_menu_button, $nots, $num_emojis;
                        if ($message['text'] == 'Возврат в главное меню')
                            go_menu($peer_id, 'main');
                        else if(!$student['notif'])
                        {
                            preg_match_all('/^(\d{1,2}):(\d{2})$/', $message['text'], $matches);
                            if($matches[0] and 6 <= $matches[1][0] and $matches[1][0] < 23 and 0 <= $matches[2][0] and $matches[2][0] <= 59)
                            {
                                $conn->query("update students set notif = '".$message['text']."', notificated = FALSE where user_id = $peer_id");
                                send_message($peer_id, "Отлично! Ежедневные уведомления настроены.\nВы будете получать уведомления каждый день в ".$message['text'], ['inline'=>true,'buttons'=>[$main_menu_button]]);
                            }
                            else
                                send_message($peer_id, $notifications_greeding_message, ['inline'=>true,'buttons'=>[$main_menu_button]]);
                        }
                        else
                            switch($message['text'])
                            {
                                case 1:
                                    $conn->query("update students set notif = '' where user_id = $peer_id");
                                    send_message($peer_id, 'Пришли мне новое время.', ['inline'=>true,'buttons'=>[$main_menu_button]]);
                                    break;
                                case 2:
                                    global $default_message, $default_keyboard;
                                    $conn->query("update students set notif = '' where user_id = $peer_id");
                                    send_message($peer_id, 'Ежедневные уведомления отключены.');
                                    go_menu($peer_id, 'main');
                                    break;
                                case 3:  // В главное меню
                                    go_menu($peer_id, 'main');
                                    break;
                                default:
                                    send_message($peer_id, "Вы получаете уведомления каждый день в ".$student['notif']."\n".$notification_menu_message, $notification_menu_keyboard);
                                    break;
                            }
                        break;
                    case 'other_schedule':
                        if ($message['text'] == 'Возврат в главное меню')
                            go_menu($peer_id, 'main');
                        else
                        {
                            global $other_schedule_menu_message, $main_menu_button;
                            $menu_keyboard = ['inline' => true,'buttons' => [$main_menu_button]];
                            if ($gr = $conn->query("select banned, doc_name from groups where name='".$message['text']."'")->fetch_assoc())
                            {
                                if($gr['banned'])
                                    send_message($peer_id, 'Расписание данной группы не доступно по техническим причинам, приносим извинения за неудобства');
                                else
                                {
                                    $ev_w = boolval(date('W')%2);
                                    $message_text = "Расписание группы: ".$message['text']."\n";
                                    $message_text .= "На эту неделю (".($ev_w?'чётная': 'нечётная')." неделя):\n";
                                    $message_text .= get_schedule($gr['doc_name'], $ev_w);
                                    $message_text .= "\nНа следующую неделю (".(!$ev_w?'чётная': 'нечётная')." неделя):\n";
                                    $message_text .= get_schedule($gr['doc_name'], !$ev_w);
                                    send_message($peer_id, $message_text, $menu_keyboard);
                                }
                            } else
                                send_message($peer_id, $other_schedule_menu_message, $menu_keyboard);
                        }
                        break;

                    case 'teachers_schedule':
                        if ($message['text'] == 'Возврат в главное меню')
                            go_menu($peer_id, 'main');
                        else
                        {
                            global $teacher_menu_message, $main_menu_button;
                            $menu_keyboard = ['inline' => true,'buttons' => [$main_menu_button]];
                            if ($t = $conn->query("select doc_name from teachers where teacher='".$message['text']."'")->fetch_assoc())
                            {
                                //$ev_w = boolval(date('W')%2);
                                $message_text = "Расписание преподавателя: ".$message['text']."\n";
                                    $message_text .= "На эту неделю (".($ev_w?'чётная': 'нечётная')." неделя):\n";
                                    $message_text .= get_schedule($t['doc_name'], $ev_w, 6, TRUE);
                                    $message_text .= "\nНа следующую неделю (".(!$ev_w?'чётная': 'нечётная')." неделя):\n";
                                    $message_text .= get_schedule($t['doc_name'], !$ev_w, 6, TRUE);
                                send_message($peer_id, $message_text, $menu_keyboard);
                            } else
                                send_message($peer_id, $teacher_menu_message, $menu_keyboard);
                        }
                        break;
                    case 'delete_confirm':
                        switch($message['text'])
                        {
                            case 'Да':  // Удалить
                                global $successeful_delete_message;
                                $conn->query("delete from students where user_id = $peer_id");
                                send_message($peer_id, $successeful_delete_message);
                                break;
                            case 'Нет': // Назад в главное меню
                                go_menu($peer_id, 'main');
                                break;
                            default:
                                global $delete_menu_keyboard;
                                send_message($peer_id, 'Вы уверены, что хотите удалить данные о себе?', $delete_menu_keyboard);
                                break;
                        }
                        break;
                    case 'admin':
                        switch ($message['text']){
                            case 1:
                                go_menu($peer_id, 'blacklist_group');
                                break;
                            case 2:
                                global $cancel_button;
                                $res = $conn->query("select name from groups where banned = 1");
                                $message_text = '';
                                $i = 0;
                                while ($gr = $res->fetch_assoc())
                                    $message_text .= ++$i.". ".$gr['name']."\n";
                                if (!$i)
                                    $message_text = 'Вы пока не занесли ни одну группу в чс';
                                else
                                    $message_text = "Список групп, занесённых в чёрный список:\n".$message_text;
                                send_message($peer_id, $message_text, ['inline'=>true, 'buttons'=>[$cancel_button]]);
                                break;
                            case 3:
                                go_menu($peer_id, 'blacklist_user');
                                break;
                            case 4:
                                global $cancel_button;
                                $res = $conn->query("select user_id from students where banned = 1");
                                $message_text = '';
                                $i = 0;
                                while ($st = $res->fetch_assoc())
                                {
                                    $info = api('users.get', ['user_ids'=>$st['user_id']])[0];
                                    var_dump($info);
                                    $message_text .= ++$i.". [id".$st['user_id']."|".$info['first_name']." ".$info['last_name']."]\n";
                                }
                                if (!$i)
                                    $message_text = 'Вы пока не занесли ни одного пользователя в чс';
                                else
                                    $message_text = "Список пользователей, занесённых в чёрный список:\n".$message_text;
                                send_message($peer_id, $message_text, ['inline'=>true, 'buttons'=>[$cancel_button]]);
                                break;
                            case 5:
                                go_menu($peer_id, 'get_group_ids');
                                break;
                            case 6:
                                go_menu($peer_id, 'main');
                                break;
                            case 'Возврат в главное меню':
                                go_menu($peer_id, 'main');
                                break;
                            default:
                                global $admin_message, $admin_keyboard;
                                send_message($peer_id, $admin_message, $admin_keyboard);
                                break;
                        }
                        break;
                    case 'blacklist_user':
                        preg_match('/\[id(\d+?)\|.+?\]/', $message['text'], $matches);
                        if ($message['text'] == 'Назад')
                            go_menu($peer_id, 'admin');
                        elseif ($matches[0])
                        {
                            global $cancel_button;
                            $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button]];
                            $banned = $conn->query("select banned from students where user_id=$peer_id")->fetch_assoc()['banned'];
                            if ($banned !== NULL)
                                if ($banned == '1')
                                {
                                    $conn->query("update students set banned = FALSE where user_id=".$matches[1]);
                                    send_message($peer_id, '[id'.$matches[1].'|Пользователь] удалён из чс');
                                } else {
                                    $conn->query("update students set banned = TRUE where user_id=".$matches[1]);
                                    send_message($peer_id, '[id'.$matches[1].'|Пользователь] занесён в чс');
                                }
                            else
                                send_message($peer_id, '[id'.$matches[1].'|Пользователь] отсутствует в базе данных бота');
                            go_menu($peer_id, 'admin');
                        }
                        else
                        {
                            global $cancel_button;
                            $menu_keyboard =
                            ['inline' => true, 'buttons' => [$cancel_button]];
                            send_message($peer_id, 'Пришли мне вк пользователя', $menu_keyboard);
                        }
                        break;
                    case 'blacklist_group':
                        if ($message['text'] == 'Назад')
                            go_menu($peer_id, 'admin');
                        else
                        {
                            global $cancel_button;
                            $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button]];
                            $banned = $conn->query("select banned from groups where name = '".$message['text']."'")->fetch_assoc()['banned'];
                            if ($banned !== NULL)
                                if ($banned == '1')
                                {
                                    $conn->query("update groups set banned = FALSE where name = '".$message['text']."'");
                                    send_message($peer_id, 'Группа '.$message['text'].' удалена из чс');
                                } else {
                                    $conn->query("update groups set banned = TRUE where name = '".$message['text']."'");
                                    $conn->query("delete from students where group = '".$message['text']."'");
                                    send_message($peer_id, 'Группа '.$message['text'].' занесена в чс');
                                }
                            else
                                send_message($peer_id, "Группа '".$message['text']."' отсутствует в базе данных бота");
                            go_menu($peer_id, 'admin');
                        }
                        break;
                    case 'get_group_ids':
                        if ($message['text'] == 'Назад')
                            go_menu($peer_id, 'admin');
                        elseif($rows = $conn->query("select user_id from students where grup = '".$message['text']."'")){
                            $res = '';
                            while($row = $rows->fetch_assoc())
                                $res .= '[id'.$row['user_id'].'|'.$row['user_id']."]\n";
                            if (!$res)
                                $res = 'В этой группе нет ни одного человека';
                            $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button]];
                            send_message($peer_id, 'Вот список людей из группы '.$message['text'].":\n".$res, $menu_keyboard);
                        }
                        else
                        {
                            global $cancel_button;
                            $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button]];
                            send_message($peer_id, 'Пришли мне название группы', $menu_keyboard);
                        }
                        break;
                }
        }
        else // --Регистрация--
        {
            if(!$student['faculty'])  // Проверяем записан ли его факультет
            {
                if(array_key_exists($message['text'], FACS))
                {
                    $conn->query("update students set faculty = '".$message['text']."' where user_id = $peer_id");
                    $student['faculty'] = $message['text'];
                }
                else
                {
                    global $greedings_message;
                    send_message($peer_id, $greedings_message);
                    $buttons = array_to_buttons(array_keys(FACS));
                    $fac_keyboard =
                    ['inline' => true,'buttons' => $buttons];
                    send_message($peer_id, 'Выбери свой факультет:', $fac_keyboard);
                }
            }
            // Проверяем записан ли его курс
            if($student['faculty'] and $student['course'] == -1)
            {
                if ($student['faculty'] == 'Магистратура')
                    $courses = ['1 курс', '2 курс'];
                else
                    $courses = ['1 курс', '2 курс', '3 курс', '4 курс', '5 курс'];
                $ind = array_search($message['text'], $courses);
                if($ind !== FALSE)
                {
                    $conn->query("update students set course = ".($ind+1)." where user_id = $peer_id");
                    $student['course'] = $ind+1;
                }
                elseif($message['text'] == 'Назад')
                {
                    $conn->query("update students set faculty = '' where user_id = $peer_id");
                    $buttons = array_to_buttons(array_keys(FACS));
                    $fac_keyboard =
                    ['inline' => true,'buttons' => $buttons];
                    send_message($peer_id, 'Выбери свой факультет:', $fac_keyboard);
                }
                else
                {
                    global $cancel_button;
                    $buttons = array_to_buttons($courses, 2);
                    array_push($buttons, $cancel_button);
                    $keyboard =
                    ['inline' => true,'buttons' => $buttons];
                    send_message($peer_id, 'На каком ты курсе?', $keyboard);
                }
            }
            // Проверяем записана ли его группа
            if($student['faculty'] and $student['course'] != -1 and !$student['grup'])
            {
                $res = $conn->query("select name from groups where faculty = '".$student['faculty']."' and course = '".$student['course']."' and banned = FALSE");
                $groups = [];
                while ($gr = $res->fetch_assoc())
                    array_push($groups, $gr['name']);
                if(in_array($message['text'], $groups))
                {
                    $conn->query("update students set grup = '".$message['text']."', registered = TRUE where user_id = $peer_id");
                    $student['grup'] = $message['text'];
                }
                elseif($message['text'] == 'Назад')
                {
                    if ($student['faculty'] == 'Магистратура')
                        $courses = ['1 курс', '2 курс'];
                    else
                        $courses = ['1 курс', '2 курс', '3 курс', '4 курс', '5 курс'];
                    $conn->query("update students set course = -1 where user_id = $peer_id");
                    global $cancel_button;
                    $buttons = array_to_buttons($courses, 2);
                    array_push($buttons, $cancel_button);
                    $keyboard =
                    ['inline' => true,'buttons' => $buttons];
                    send_message($peer_id, 'На каком ты курсе?', $keyboard);
                }
                else
                {
                    global $cancel_button;
                    $buttons = array_to_buttons($groups, 3);
                    array_push($buttons, $cancel_button);
                    $keyboard =
                    ['inline' => true,'buttons' => $buttons];
                    send_message($peer_id, 'В какой ты группе?', $keyboard);
                }
            }
            if($student['faculty'] and $student['course'] != -1 and $student['grup'])
            {
                global $default_message, $default_keyboard, $admin_button;
                $menu_keyboard = $default_keyboard;
                if ($admin or $kirill)
                    array_push($menu_keyboard['buttons'], $admin_button);
                send_message($peer_id, $default_message, $menu_keyboard);
            }
        }
    }
    else
    {
        global $greedings_message;
        send_message($peer_id, $greedings_message);
        $conn->query("insert into students values(".$peer_id.",0, '', -1, '', '', 0, 0, 'main')");
        $fac_keyboard =
        ['inline' => true,'buttons' => array_to_buttons(array_keys(FACS))];
        send_message($peer_id, 'Выбери свой факультет:', $fac_keyboard);
    }
    echo 'ok';
}
else if ($update['type'] == 'message_deny')
{
    global $conn;
    $conn->query('delete from students where user_id = '.$update['object']['user_id']);
    echo 'ok';
}
elseif($update['type'] == 'confirmation')
    echo CONFIRMATION_TOKEN;
?>