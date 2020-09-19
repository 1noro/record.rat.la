<?php
    $articles_to_show = 3;
    $directory = 'article/';
    $authors = [
        "a" => ["Anon", "202009180000i-404.html"],
        "i" => ["Inoro", "202009180002i-inoro.html"]
    ];
    $color_id = 0;
    $colors = [
        [
            "background" => "#EDD1B0", // Peach: #EDD1B0, Orange: #EDDD6E, Yellow: #F8FD89, 4chan: #FFFFEE
            "text" => "#000000",
            "link" => "auto",
            "link_visited" => "auto",
            "link_active" => "auto",
            "code_background" => "#FFFFEE", // #dfdebe, #f8bba5
            "code_text" => "inherit"
        ],
        [
            "background" => "#000000",
            "text" => "#FFFFFF",
            "link" => "#ffff00",
            "link_visited" => "#cccc00",
            "link_active" => "#0000ff",
            "code_background" => "#FFFFFF",
            "code_text" => "#000000"
        ]
    ];

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

    function get_date($filename) {
        $year = substr($filename, 0, 4);
        $month = substr($filename, 4, 2);
        $day = substr($filename, 6, 2);
        $hour = substr($filename, 8, 2);
        $minute = substr($filename, 10, 2);
        $result = $year."/".$month."/".$day." ".$hour.":".$minute;
        return $result;
    }

    function get_author_data($filename) {
        $authorid = substr($filename, 12, 1);
        if (array_key_exists($authorid, $GLOBALS["authors"])) {
            $result = $GLOBALS["authors"][$authorid];
        } else {
            $result = $GLOBALS["authors"]["a"];
        }
        return $result;
    }

    function get_title($filepath) {
        $file_obj = fopen($filepath, "r");
        $result = fgets($file_obj);
        $result = str_replace("<h2>", "", $result);
        $result = str_replace("</h2>", "", $result);
        fclose($file_obj);
        return $result;
    }

    function print_article($directory, $filename) {
        echo file_get_contents($directory . $filename);
        echo "<p style=\"text-align:right;\"><small><a href=\"index.php?q=" . get_author_data($filename)[1] . "\">" . get_author_data($filename)[0] . "</a> - " . get_date($filename) . " - <a href=\"index.php?q=" . $filename . "\">enlace</a></p></small>";
    }

    function print_reciente($directory, $filenames, $articles_to_show) {
        $i = 1;
        foreach($filenames as $filename) {
            print_article($directory, $filename);
            if ($i >= $articles_to_show) {break;}
            echo "<hr>";
            $i++;
        }
        echo "<br><p class=\"center\"><a href=\"index.php?q=h\">Más artículos</a></p>";
    }

    function print_historico($directory, $filenames) {
        echo "<h2>Histórico de posts</h2>";
        echo "<ul>";
        foreach($filenames as $filename) {
            echo "<li><a href=\"index.php?q=" . $filename . "\">" . get_date($filename) . "</a> (" . get_author_data($filename)[0] . ") " . get_title($directory . $filename) . "</li>";
        }
        echo "</ul>";
    }

    // procesamos la variable GET "q"
    $action = 0;
    $filenames = get_filenames($directory);
    if (isset($_GET["q"])) {
        if ($_GET["q"] == "h") {
            // print_historico($directory, $filenames);
            $action = 1;
        } elseif ($_GET["q"] == "c" and isset($_GET["c"])) {
            echo "HOLA: " . $_GET["c"] . "<br>";
            $action = 2;
        } else {
            if (in_array($_GET["q"], $filenames)) {
                // print_article($directory, $_GET["q"]);
                $action = 3;
            } else {
                // print_article($directory, "202009180000i-404.html");
                $action = 404;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="Inoro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="description" content="Blog/Web personal donde iré registrando mis proyectos y mis fumadas mentales.">

        <title>record.rat.la</title>
        <link rel="icon" href="favicon.png" type="image/png" sizes="16x16">

        <style>
            body {
                background-color: <?php echo $colors[$color_id]["background"]; ?>;
                color: <?php echo $colors[$color_id]["text"]; ?>;
                font-size: 1.35em; /*revisar*/
                font-family: Times, Serif; /*Considerar obviar la letra Times y poner todo Serif*/
            }

            header, footer, p.center {text-align: center;}

            div#content {
                width: 100%;
                max-width: 750px;
                margin: 0px auto;
            }

            pre {
                padding: 10px;
                overflow: auto;
            }

            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?php echo $colors[$color_id]["link"]; ?>;}
            a:visited {color: <?php echo $colors[$color_id]["link_visited"]; ?>;}
            a:active {color: <?php echo $colors[$color_id]["link_active"]; ?>;}

            pre, code {
                background-color: <?php echo $colors[$color_id]["code_background"]; ?>;
                color: <?php echo $colors[$color_id]["code_text"]; ?>;
            }
        </style>
    </head>

    <body>
        <header>
            <h1>record.rat.la</h1>
            <p>
                <a href="index.php" title="Los últimos posts">reciente</a> / <a href="index.php?q=h" title="Todos los post ordenados por fecha">histórico</a> / <a href="index.php?q=202009180001i-faq.html" title="¿Qué es esta página?">faq</a> / <a href="index.php?q=202009180003i-color.html">color</a>
            </p>
        </header>

        <div id="content">
            <?php
                $filenames = get_filenames($directory);
                switch ($action) {
                    case 0:
                        print_reciente($directory, $filenames, $articles_to_show);
                        break;
                    case 1:
                        print_historico($directory, $filenames);
                        break;
                    case 2:
                        print_article($directory, "202009180003i-color.html");
                        break;
                    case 3:
                        print_article($directory, $_GET["q"]);
                        break;
                    case 404:
                        print_article($directory, "202009180000i-404.html");
                        break;
                }
            ?>
        </div>

        <footer>
            <br>
            <p>
                <small><a href="https://github.com/1noro">github</a> / <a href="https://gitlab.com/1noro">gitlab</a> / <a href="https://twitter.com/0x12Faab7">twiter</a> / <a href="mailto:ppuubblliicc@protonmail.com">mail</a> (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" title="¡Mándame un correo cifrado!">gpg</a>)<br></small>
            </p>
            <p>
                <small>Creado por <a href="https://github.com/1noro/record.rat.la">Inoro</a> bajo la licencia <a href="LICENSE" title="Todo el código que sustenta la web está bajo la licencia GPLv3.">GPLv3</a></small>
                <br>
                <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/" title="Todo el contenido multimedia está bajo la licencia CC-BY-NC-SA."><img alt="Licencia de Creative Commons BY-NC-SA" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-sa/4.0/80x15.png"/></a>
            </p>
        </footer>
    </body>
</html>
