<?php
    include 'simple_html_dom.php';
    define('TZ', 'Europe/Samara');  
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'db_username');
    define('DB_PASSWORD', 'db_password');
    define('DB', 'db_name');
    define('CONFIRMATION_TOKEN', 'token');
    define('SECRET_KEY', 'AABBAABBCC');
	define('TOKEN', 'secret_key');
    define('V',"5.126");
    define('ADMIN_ID', 1);
    define('KIRILL_ID', 1);
    define('PRASPISAN', 'https://www.samgups.ru/raspisanie/2020-2021/vtoroy-semestr/HTML_PREPS/Praspisan.html');
    define('RASPISAN', 'https://www.samgups.ru/raspisanie/2020-2021/vtoroy-semestr/HTML/raspisan.html');
    define('T_WIDTH', 8);
    define('PT_WIDTH', 9);
    define('FACS',[
        'Магистратура' => ['дИВТм', 'дИСТм', 'дСАУм', 'дТТПм', 'дМм', 'дУПм', 'дЭм'],
        'ПС и ПМ' => ['дНТТС', 'дПСЖД', 'дЭТТМКб', 'дЭЭб', 'дНТТКб'],
        'СИТ' => ['дИВТб','дИСТб','дМРб','дСАУб','дСЖД','дСб'],
        'СОДП' => ['дСМб', 'дСОДП'],
        'ЭЖД' => ['дТТПб', 'дТбб', 'дЭЖД'],
        'ЭЛМ' => ['дМб', 'дУПб', 'дЭб']]);
    $greedings_message = <<<EOT
Привет!  Я бот, который оповестит  тебя о  твоём расписание пар в СамГУПС! Что я умею:
–показывать расписание на сегодня, завтра, эту неделю, следующую неделю;
–ежедневно уведомлять о расписание на предстоящие пары (рассылка);
–показывать расписание другой группы;
–показывать расписание занятий преподавателей.
EOT;
    $default_message=<<<EOT
1. Посмотреть своё расписание(на сегодня/завтра/эту неделю/следующую неделю).
2. Настроить уведомления(бот будет каждый день присылать расписание на следующий день).
3. Посмотреть расписание другой группы.
4. Посмотреть расписание преподавателя.
5. Назад.
6. Удалить все данные о себе.
EOT;
    $admin_message=<<<EOT
1. Удалить/добавить расписание группы.
2. Вывести список групп, занесённых в чс.
3. Добавить/убрать пользователя в (из) чс.
4. Вывести список пользователей, которые в чс.
5. Собрать id участников группы.
6.Возврат в пользовательское меню
EOT;
    $schedule_menu_message=<<<EOT
1. Расписание на сегодня.
2. Расписание на завтра.
3. Расписание на эту неделю.
4. Расписание на следующую неделю.
5. Возврат в главное меню.
EOT;
    $teacher_menu_message='Введи  фамилию  и  инициалы,  чтобы  узнать  расписание  нужного преподавателя, например: Иванов. А.А.';
    $other_schedule_menu_message='Пришли мне название другой группы, например: дПСЖД01 или дЭЖД91 или дМРб81.';
    $successeful_delete_message='Ваши данные успешно удалены, напишите что-нибудь, чтобы вновь пройти регистрацию';
    $notifications_greeding_message='Пришли мне время, в которое я тебе буду присылать каждый день расписание на завтра. '.
    'Например, 21:00 или 09:37(можно выбрать любое время от 06:00 до 23:00).';
    $notification_menu_message=<<<EOM
1. Изменить время уведомлений.
2. Отписаться(отключить)от уведомлений.
3. Возврат в главное меню;
EOM;
    $weekdays = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
    $pair_time = ['08:30-10:00', '10:15-11:45', '12:10-13:40', '13:55-15:25', '15:35-17:05', '17:15-18:45'];
    $num_emojis = ['0&#65039;&#8419;','1&#65039;&#8419;', '2&#65039;&#8419;','3&#65039;&#8419;'
                    ,'4&#65039;&#8419;','5&#65039;&#8419;','6&#65039;&#8419;'
                    ,'7&#65039;&#8419;','8&#65039;&#8419;','9&#65039;&#8419;'];
    $cancel_button =  [['action'=>['type'=>'text','label'=>'Назад'],'color'=>'primary']];
    $main_menu_button = [['action'=>['type'=>'text','label'=>'Возврат в главное меню'],'color'=>'primary']];
    $admin_button = [['action'=>['type'=>'text','label'=>'Меню администратора'],'color'=>'positive']];
    $admin_keyboard = [
        'one_time' => true,
        'buttons' => [[[
            'action' =>
            [
                'type' => 'text',
                'label' => '1',
            ],
            'color' => 'secondary'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => '2',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '3',
            ],
            'color' => 'secondary'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => '4',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '5',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '6',
            ],
            'color' => 'primary'
        ]]
    ]];
    $notification_menu_keyboard =
    [
        'one_time' => true,
        'buttons' => [
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '1',
            ],
            'color' => 'secondary'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => '2',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '3',
            ],
            'color' => 'primary'
        ]]]
    ];
    $schedule_menu_keyboard =
    [
        'one_time' => true,
        'buttons' => [
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '1',
            ],
            'color' => 'secondary'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => '2',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '3',
            ],
            'color' => 'secondary'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => '4',
            ],
            'color' => 'secondary'
        ]],
        [[
            'action' =>
            [
                'type' => 'text',
                'label' => '5',
            ],
            'color' => 'primary'
        ]]]
    ];
    $delete_menu_keyboard =
    [
        'inline' => true,
        'buttons' => [[
        [
            'action' =>
            [
                'type' => 'text',
                'label' => 'Да',
            ],
            'color' => 'negative'
        ], [
            'action' =>
            [
                'type' => 'text',
                'label' => 'Нет',
            ],
            'color' => 'positive'
        ]]]
    ];
    $default_keyboard =
    [
        'one_time' => true,
        'buttons' =>
        [
            [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '1'
                    ],
                    'color' => 'secondary'
                ],
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '2'
                    ],
                    'color' => 'secondary'
                ],
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '3'
                    ],
                    'color' => 'secondary'
                ],
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '4'
                    ],
                    'color' => 'secondary'
                ]
            ],
            [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => '5'
                    ],
                    'color' => 'primary'
                ],
                [
                    'action' => [
                        'type' => 'text',
                        'label' => 6
                    ],
                    'color' => 'primary'
                ]
            ]
        ],
    ];
    
    
function api($method, $params, $post=false) // Запрос в апи вк метод $method, параметры $params
{
    $default = ['access_token' => TOKEN,'v' => V];
    $url = 'https://api.vk.com/method/' . $method;

    if($post)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array_merge($default, $params));
    }
    else
    {
        $url .= '?'.http_build_query(array_merge($default, $params));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);     
    if (curl_errno($curl))
    {
        echo 'Ошибка'.curl_error($curl);
        return null;
    }
    curl_close($curl);
    return json_decode($response, true);
}

function send_message($peer_id, $message_text, $keyboard=[]) // Отправка сообщения $message_text
{
    api('messages.send',
    [
        'random_id' => rand(0, 999999999),
        'peer_id' => $peer_id,
        'message' => $message_text,
        'keyboard' => json_encode($keyboard)
    ],true);
}

function array_to_buttons($array, $max_width=1) // Генерирует массив кнопок на основе массива строк
{
    $buttons = [];
    $row = [];
    foreach($array as $el)
    {
        $button =
        [
            'action' => [
                'type' => 'text',
                'label' => $el
            ],
            'color' => 'primary'
        ];
        array_push($row, $button);
        if(count($row) == $max_width){
            array_push($buttons, $row);
            $row = [];
        }
    }
    if(count($row) > 0)
        array_push($buttons, $row);
    return $buttons;
}

function get_schedule1($doc_link, $even_week, $dayN=6) // Возвращает расписание группы на $even_week неделю
{
    global $weekdays, $pair_time, $num_emojis;
    $dom = file_get_html($doc_link);
    if ($even_week)
        $rows = array_slice($dom->find('table')[1]->find('tr'), 2, 6);
    else
        $rows = array_slice($dom->find('table')[0]->find('tr'), 2, 6);
    $res = '';
    if ($dayN == 6) foreach($rows as $row) {
        $pairs = '';
        $pn = 0;
        foreach(array_slice($row->find('td'), 1) as $subj)
        {
            if (!strpos($subj->plaintext, '_') and !ctype_space($subj->plaintext) and !empty($subj->plaintext))
                $pairs .= str_replace("\n", "", $num_emojis[$pn+1].' '.$pair_time[$pn].' '.$subj->plaintext)."\n";
            $pn++;
        }
        if ($pairs)
            $res .= "&#10145;$weekdays[$dayn]&#11013;\n".$pairs."\n";
    } else {
        $pairs = '';
        $pn = 0;
        foreach(array_slice($rows[dayN]->find('td'), 1) as $subj)
        {
            if (!strpos($subj->plaintext, '_') and !ctype_space($subj->plaintext) and !empty($subj->plaintext))
                $pairs .= str_replace("\n", "", $num_emojis[$pn+1].' '.$pair_time[$pn].' '.$subj->plaintext)."\n";
            $pn++;
        }
        if ($pairs)
            $res .= "&#10145;$weekdays[$dayN]&#11013;\n".$pairs;
        else
            return FALSE;
    }
    return $res;
}

function get_schedule($dn, $even_week, $dayN=6, $p = FALSE) // Возвращает расписание группы на $even_week неделю
{
    global $weekdays, $pair_time, $num_emojis;
    if ($p === FALSE)
    {
        $arr = explode('/', RASPISAN);
        $twidth = T_WIDTH;
    }
    else
    {
        $arr = explode('/', PRASPISAN);
        $twidth = PT_WIDTH;
    }
    $doc_link = implode('/', array_slice($arr,0,count($arr)-1)).'/'.$dn;
    $dom = file_get_html($doc_link);
    if ($even_week)
        $cells = array_slice($dom->find('table')[1]->find('td'), 2*$twidth + 2);
    else
        $cells = array_slice($dom->find('table')[0]->find('td'), 2*$twidth + 2);
    $res = '';
    //for($n = 0; $n < count($cells); $n++)
        //echo '<p>#'.$n.' - "'.$cells[$n]->plaintext.'"</p>';
    if ($dayN == 6) for($dayn = 0; $dayn<6; $dayn++) {
        $pairs = '';
        $pn = 0;
        //var_dump(count(array_slice($cells, $dayn*$twidth + 1, $twidth)));
        for($n = $dayn*($twidth+1)+1; $n < $dayn*($twidth+1)+1 + $twidth; $n++)
        {
            //echo '<p>#'.$n.' - "'.$cells[$n]->plaintext.'"'.$pn.'</p>';
            $text = preg_replace('/<\/.*>|_|Сбт|Вск|Птн|Чтв|Срд|Втр|Пнд/', '', $cells[$n]->plaintext);
            //echo '<p>"'.$text.'"</p>';
            if (!ctype_space($text) and !empty($text))
                $pairs .= str_replace("\n", "", $num_emojis[$pn+1].' '.$pair_time[$pn].' '.$text)."\n";
            $pn++;
        }
        if ($pairs)
            $res .= "&#10145;$weekdays[$dayn]&#11013;\n".$pairs."\n";
    } else {
        $pairs = '';
        $pn = 0;
        for($n = $dayN*($twidth+1)+1; $n < $dayN*($twidth+1)+1 + $twidth; $n++)
        {
            //echo '<p>#'.$n.' - "'.$cells[$n]->plaintext.'"'.$pn.'</p>';
            //echo '<p>'.$n.'</p>';
            $text = preg_replace('/<\/.*>|_|Сбт|Вск/', '', $cells[$n]->plaintext);
            if (!ctype_space($text) and !empty($text))
                $pairs .= str_replace("\n", "", $num_emojis[$pn+1].' '.$pair_time[$pn].' '.$text)."\n";
            $pn++;
        }
        if ($pairs)
            $res .= "&#10145;$weekdays[$dayN]&#11013;\n".$pairs;
        else
            return FALSE;
    }
    return $res;
}

function get_teachers_schedule($dn)
{
    global $weekdays, $pair_time, $num_emojis;
    $arr = explode('/', PRASPISAN);
    $doc_link = implode('/', array_slice($arr,0,count($arr)-1)).'/'.$dn;
    $twidth = PT_WIDTH;
    $dom = file_get_html($doc_link);
    $tables = $dom->find('table');
    preg_match_all('/\d{2}.\d{2}.\d{4} - \d{2}.\d{2}.\d{4}/', $dom->plaintext, $matches);
    $res = '';
    for ($tn = 0; $tn < count($matches[0]);$tn++)
    {
        $cells = array_slice($dom->find('table')[$tn]->find('td'), 2*$twidth + 2);
        $res .= $matches[0][$tn]."\n";
        for($dayn = 0; $dayn<6; $dayn++) 
        {
            $pairs = '';
            $pn = 0;
            //var_dump(count(array_slice($cells, $dayn*$twidth + 1, $twidth)));
            for($n = $dayn*($twidth+1)+1; $n < $dayn*($twidth+1)+1 + $twidth; $n++)
            {
                //echo '<p>#'.$n.' - "'.$cells[$n]->plaintext.'"'.$pn.'</p>';
                $text = preg_replace('/<\/.*>|_|Сбт|Вск|Птн|Чтв|Срд|Втр|Пнд/', '', $cells[$n]->plaintext);
                //echo '<p>"'.$text.'"</p>';
                if (!ctype_space($text) and !empty($text))
                    $pairs .= str_replace("\n", "", $num_emojis[$pn+1].' '.$pair_time[$pn].' '.$text)."\n";
                $pn++;
            }
            if ($pairs)
                $res .= "&#10145;$weekdays[$dayn]&#11013;\n".$pairs."\n";
        }
    }
    return $res;
}

function get_week_str($date, $line=FALSE)
{
    $weekday = date("N");
    if ($line === TRUE)
    {
        $week_start = date("d.m.Y", strtotime("-" . ($weekday - 1) . " days", strtotime($date)));
        $week_end = date("d.m.Y", strtotime("+" . (7 - $weekday) . " days", strtotime($date)));
        return $week_start.' - '.$week_end;
    }
    else
    {
        $week_start = date("d-m-Y", strtotime("-" . ($weekday - 1) . " days", strtotime($date)));
        $week_end = date("d-m-Y", strtotime("+" . (7 - $weekday) . " days", strtotime($date)));
        return 'с '.$week_start.' по '.$week_end;
    }
}

function go_menu($peer_id, $menu)
{
    global $student, $admin, $kirill, $conn;
    $conn->query("update students set menu = '".$menu."' where user_id = $peer_id");
    switch ($menu)
    {
        case 'main':
            global $default_message, $default_keyboard, $admin_button;
            $menu_keyboard = $default_keyboard;
            if ($admin or $kirill)
                array_push($menu_keyboard['buttons'], $admin_button);
            send_message($peer_id, $default_message, $menu_keyboard);
            break;
        case 'schedule':
            global $schedule_menu_message, $schedule_menu_keyboard;
            send_message($peer_id, $schedule_menu_message, $schedule_menu_keyboard);
            break;
        case 'notifications':
            global $notifications_greeding_message, $notification_menu_message, $notification_menu_keyboard, $main_menu_button;
            if(!$student['notif'])
                send_message($peer_id, $notifications_greeding_message, ['inline'=>true,'buttons'=>[$main_menu_button]]);
            else
                send_message($peer_id, "Вы получаете уведомления каждый день в ".$student['notif']."\n".$notification_menu_message, $notification_menu_keyboard);
            break;
        case 'other_schedule':
            global $other_schedule_menu_message, $main_menu_button;
            $menu_keyboard =
            ['inline' => true, 'buttons' => [$main_menu_button]];
            send_message($peer_id, $other_schedule_menu_message, $menu_keyboard);
            break;
        case 'teachers_schedule':
            global $teacher_menu_message, $main_menu_button;
            $menu_keyboard =
            ['inline' => true,'buttons' => [$main_menu_button]];
            send_message($peer_id, $teacher_menu_message, $menu_keyboard);
            break;
        case 'delete_confirm':
            global $delete_menu_keyboard;
            send_message($peer_id, 'Вы уверены, что хотите удалить данные о себе?', $delete_menu_keyboard);
            break;
        case 'admin':
            global $admin_message, $admin_keyboard;
            send_message($peer_id, $admin_message, $admin_keyboard);
            break;
        case 'blacklist_user':
            global $cancel_button;
            $menu_keyboard =
            ['inline' => true, 'buttons' => [$cancel_button]];
            send_message($peer_id, 'Пришли мне вк пользователя', $menu_keyboard);
            break;
        case 'blacklist_group':
            global $cancel_button;
            $menu_keyboard =
            ['inline' => true, 'buttons' => [$cancel_button]];
            send_message($peer_id, 'Пришли мне название группы', $menu_keyboard);
            break;
        case 'get_group_ids':
            global $cancel_button;
            $menu_keyboard = ['inline' => true, 'buttons' => [$cancel_button]];
            send_message($peer_id, 'Пришли мне название группы', $menu_keyboard);
            break;
    }
}
?>