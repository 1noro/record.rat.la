<?php
    $articles_to_show = 2;
    $directory = '../article/';

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

    function print_reciente($directory, $filenames, $articles_to_show) {
        $i = 1;
        foreach($filenames as $filename) {
            echo file_get_contents($directory . $filename);
            echo "<p style=\"text-align:right;\"><small>" . get_date($filename) . "</p></small>";
            if ($i >= $articles_to_show) {break;}
            echo "<hr>";
            $i++;
        }
    }

    $filenames = get_filenames($directory);
    print_reciente($directory, $filenames, $articles_to_show);
?>
