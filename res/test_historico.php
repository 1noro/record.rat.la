<?php
    $articles_to_show = 2;
    $directory = '../article/';

    function get_title($filepath) {
        $file_obj = fopen($filepath, "r");
        $result = fgets($file_obj);
        $result = str_replace("<h1>", "", $result);
        $result = str_replace("</h1>", "", $result);
        fclose($file_obj);
        return $result;
    }

    function get_date($filename) {
        $year = substr($filename, 0, 4);
        $month = substr($filename, 4, 2);
        $day = substr($filename, 6, 2);
        $hour = substr($filename, 8, 2);
        $minute = substr($filename, 10, 2);
        $result = $year."/".$month."/".$day." ".$hour.":".$minute;
        return $result;
    }

    function get_filenames($directory) {
        $files = array();
        $directory_obj = opendir($directory);
        while(false != ($filename = readdir($directory_obj))) {
            if(($filename != ".") and ($filename != "..")) {
                $filenames[] = $filename; // put in array.
            }
        }
        natsort($filenames); // ordenamos alfabeticamente
        $filenames = array_reverse($filenames); // le damos la vuelta a la ordenación anterior
        return $filenames;
    }

    function print_historico($directory, $filenames) {
        echo "<ul>";
        foreach($filenames as $filename) {
            echo "<li><a href='" . $directory . $filename . "'>(" . get_date($filename) . ") " . get_title($directory . $filename) . "</a></li>";
        }
        echo "</ul>";
    }

    $filenames = get_filenames($directory);
    print_historico($directory, $filenames);
?>
