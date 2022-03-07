<?php
include 'config.php';
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query('CREATE TABLE IF NOT EXISTS `groups` ( `name` VARCHAR(10) NOT NULL , `faculty` VARCHAR(12) NOT NULL , `course` TINYINT NOT NULL , `doc_name` VARCHAR(10) NOT NULL , `banned` BOOLEAN NOT NULL )');
$conn->query('CREATE TABLE IF NOT EXISTS `students` ( `user_id` INT NOT NULL , `registered` BOOLEAN NOT NULL , `faculty` VARCHAR(12) NOT NULL , `course` TINYINT NOT NULL , `grup` VARCHAR(10) NOT NULL , `notif` VARCHAR(10) NOT NULL , `banned` BOOLEAN NOT NULL , `notificated` BOOLEAN NOT NULL , `menu` VARCHAR(20) NOT NULL , PRIMARY KEY (`user_id`)) ENGINE = InnoDB;');
$conn->query('CREATE TABLE IF NOT EXISTS `teachers` ( `teacher` VARCHAR(25) NOT NULL , `doc_name` VARCHAR(10) NOT NULL )');
$conn->query('truncate groups');
$conn->query('truncate teachers');
$dom = file_get_html(SEMESTR_LINK.'raspisan.htm');
$groups = $dom->find('a font');
$link='';
foreach($groups as $group){
    if($group->innertext){
        $doc = '';
        foreach(array_keys(FACS) as $fac)
            foreach(FACS[$fac] as $gr)
            {
                $a = substr($group->innertext, 0, strlen($gr)) == $gr;
                if($a){
                    $num = explode('.',$group->parent->href)[0];
                    var_dump($group->innertext);
                    $td = $group->parent->parent->parent;
                    $course = 1;
                    while ($td = $td->prev_sibling())
                        $course++;
                    $sql = "INSERT INTO groups VALUES ('".$group->innertext."', '$fac', $course, '".$group->parent->href."', FALSE);";
                    echo '<hr width=90%>';
                    var_dump($sql);
                    echo '<hr>';
                    var_dump($conn->query($sql));
                    break;
                }
            }
    }
}

$dom = file_get_html(SEMESTR_LINK.'praspisan.htm');
$rows = $dom->find('a font');
$link='';
foreach($rows as $row){
    $sql = "INSERT INTO teachers VALUES ('".explode(',', $row->innertext)[0]."', '".$row->parent->href."');";
    var_dump(explode(',', $row->innertext)[0]);
    var_dump($conn->query($sql));
    echo '<hr>';
    }
?>