<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="1noro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="description" content="Blog/Web personal donde iré registrando mis proyectos y mis fumadas mentales.">

        <title>Record</title>
        <link rel="icon" href="favicon.png" type="image/png" sizes="16x16">

        <style>
            body {
                background-color: #EDD1B0; /*Peach: #EDD1B0*/ /*Orange: #EDDD6E*/ /*Yellow: #F8FD89*/ /*4chan: #FFFFEE*/
                color: #000000;
                font-size: 1.2em;
                font-family: Sans-serif;
            }

            header, footer {
                text-align: center;
            }

            div#content {
                width: 100%;
                max-width: 750px;
                margin: 0px auto;
            }
        </style>
    </head>

    <body>
        <header>
            <h1>record.rat.la</h1>
            <p>
                <a href="index.php" title="Los últimos posts">reciente</a> / <a href="index.php?q=h" title="Todos los post ordenados por fecha">histórico</a> / <a href="#" title="¿Qué es esta página?">faq</a>
            </p>
        </header>

        <div id="content">
            <?php
                $articles_to_show = 2;
                $directory = 'article/';
                $authors = ["a" => "anon", "i" => "1noro"];

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

                function get_author_name($filename) {
                    $authorid = substr($filename, 12, 1);
                    $result = $GLOBALS["authors"][$authorid];
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

                function print_reciente($directory, $filenames, $articles_to_show) {
                    $i = 1;
                    foreach($filenames as $filename) {
                        echo file_get_contents($directory . $filename);
                        echo "<p style=\"text-align:right;\"><small>" . get_author_name($filename) . " - " . get_date($filename) . "</p></small>";
                        if ($i >= $articles_to_show) {break;}
                        echo "<hr>";
                        $i++;
                    }
                }

                function print_historico($directory, $filenames) {
                    echo "<h2>Histórico de posts</h2>";
                    echo "<ul>";
                    foreach($filenames as $filename) {
                        echo "<li><a href='" . $directory . $filename . "'>" . get_date($filename) . " (" . get_author_name($filename) . ") " . get_title($directory . $filename) . "</a></li>";
                    }
                    echo "</ul>";
                }

                $filenames = get_filenames($directory);
                if (isset($_GET["q"])) {
                    if ($_GET["q"] == "h") {
                        print_historico($directory, $filenames);
                    }
                } else {
                    print_reciente($directory, $filenames, $articles_to_show);
                }
            ?>
        </div>

        <footer>
            <br>
            <p>
                <small><a href="https://github.com/1noro">github</a> / <a href="https://gitlab.com/1noro">gitlab</a> / <a href="https://twitter.com/0x12Faab7">twiter</a> / <a href="mailto:ppuubblliicc@protonmail.com">mail</a> (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" title="¡Mándame un correo cifrado!">gpg</a>)<br></small>
            </p>
            <p>
                <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/"><img alt="Licencia de Creative Commons" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-sa/4.0/80x15.png"/></a><br>
                <small>Creado por <a href="https://github.com/1noro/record.rat.la">1noro</a> bajo la licencia <a href="LICENSE">GPLv3</a></small>
            </p>
        </footer>
    </body>
</html>
