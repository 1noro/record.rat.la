<?php
    session_start();
    if (!isset($_SESSION["color_id"])) {
        $_SESSION["color_id"] = 0;
    }

    $articles_to_show = 2;
    $directory = 'articles/';
    $title = "Reciente - record.rat.la";
    $description = "Blog/web personal donde iré registrando mis proyectos y mis líos mentales.";
    $article_img = "img/article_def_imgP.webp";

    $authors = [
        "a" => ["Anon", "202009180000i-404.html"],
        "i" => ["Inoro", "202009180002i-inoro.html"]
    ];

    $colors = [
        [
            "background" => "#FFFFFF",
            "text" => "#222324",
            "title" => "#222324",
            "link" => "#0000EE",
            "link_visited" => "#551A8B",
            "link_active" => "#EE0000",
            "code_background" => "#222324",
            "code_text" => "#FFFFFF",
            "hedaer_img_color" => "B"
        ],
        [
            "background" => "#EDD1B0", // Peach: #EDD1B0, Orange: #EDDD6E, Yellow: #F8FD89, 4chan: #FFFFEE
            "text" => "#000000",
            "title" => "#000000",
            "link" => "#0000EE",
            "link_visited" => "#551A8B",
            "link_active" => "#EE0000",
            "code_background" => "#FFFFEE", // #dfdebe, #f8bba5
            "code_text" => "inherit",
            "hedaer_img_color" => "B"
        ],
        [
            "background" => "#000000",
            "text" => "#FFFFFF",
            "title" => "#FFFFFF",
            "link" => "#ffff00",
            "link_visited" => "#cccc00",
            "link_active" => "#0000ff",
            "code_background" => "#FFFFFF",
            "code_text" => "#000000",
            "hedaer_img_color" => "W"
        ],
        [
            "background" => "auto",
            "text" => "auto",
            "title" => "auto",
            "link" => "auto",
            "link_visited" => "auto",
            "link_active" => "auto",
            "code_background" => "auto",
            "code_text" => "auto",
            "hedaer_img_color" => "B"
        ],
        [
            "background" => "#222222",
            "text" => "#C8C8C8",
            "title" => "#ffffff",
            "link" => "#ffffff",
            "link_visited" => "#ffffff",
            "link_active" => "#ffffff",
            "code_background" => "#1f1f1f",
            "code_text" => "#c8c8c8",
            "hedaer_img_color" => "W"
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
        $result = str_replace("\n", "", $result);
        fclose($file_obj);
        return strip_tags($result); // quitamos las tags HTML
    }

    function get_url($full) {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $link = "https";
        } else {
            $link = "http";
        }
        $link .= "://";
        $link .= $_SERVER['HTTP_HOST'];
        if ($full) {$link .= $_SERVER['REQUEST_URI'];}
        return $link;
    }

    function get_description($filepath) {
        $html = file_get_contents($filepath);
        $start = strpos($html, '<p>');
        $end = strpos($html, '</p>', $start);
        $paragraph = strip_tags(substr($html, $start, $end - $start + 4));
        $paragraph = str_replace("\n", "", $paragraph);
        return trim($paragraph);
    }

    function get_article_img($filepath) {
        $html = file_get_contents($filepath);
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $html, $image);
        if (isset($image['src'])) {
            $src = $image['src'];
        } else {
            $src = $GLOBALS["article_img"];
        }
        return $src;
    }

    function print_reciente($directory, $filenames, $articles_to_show) {
        $i = 1;
        foreach($filenames as $filename) {
            print_article($directory, $filename);
            if ($i >= $articles_to_show) {break;}
            echo "<hr>";
            $i++;
        }
    }

    function print_historico($directory, $filenames) {
        echo "<h2>Histórico de artículos</h2>";
        echo "<ul>";
        foreach($filenames as $filename) {
            echo "<li><a href=\"index.php?q=" . $filename . "\">" . get_date($filename) . "</a> (" . get_author_data($filename)[0] . ") " . get_title($directory . $filename) . "</li>";
        }
        echo "</ul>";
        echo "<p>Hay un total de " . count($filenames) . " artículos en la web.</p>";
    }

    function print_article($directory, $filename) {
        echo file_get_contents($directory . $filename);
        echo "<p style=\"text-align:right;\"><small><a href=\"index.php?q=" . $filename . "\" title=\"Ver este artículo individualmente.\">Enlace al artículo</a><br><a href=\"index.php?q=" . get_author_data($filename)[1] . "\" title=\"Página del autor.\">" . get_author_data($filename)[0] . "</a> - " . get_date($filename) . "</small></p>";
    }

    // procesamos la variable GET "q"
    $action = 0;
    $filenames = get_filenames($directory);
    if (isset($_GET["q"])) {
        if ($_GET["q"] == "h") {
            // Histórico
            $action = 1;
            $title = "Histórico - record.rat.la";
            $description = "Listado de todos los artículos publicados en record.rat.la.";
        } elseif ($_GET["q"] == "c" and isset($_GET["c"])) {
            // Cambio de paleta de colores
            if ($_GET["c"] >= 0 and $_GET["c"] < count($colors)) {
                $_SESSION["color_id"] = $_GET["c"];
            }
            $action = 2;
            $title = get_title($directory . "202009180003i-color.html") . " - record.rat.la";
        } else {
            if (in_array($_GET["q"], $filenames)) {
                // Artículo
                $action = 3;
                $title = get_title($directory . $_GET["q"]) . " - record.rat.la";
                $description = get_description($directory . $_GET["q"]);
                $article_img = get_article_img($directory . $_GET["q"]);
            } else {
                // 404
                $action = 404;
                $title = get_title($directory . "202009180000i-404.html") . " - record.rat.la";
            }
        }
    }

    $color_id = $_SESSION["color_id"];
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $title; ?></title>
        <!-- <link rel="icon" href="favicon.png" type="image/png" sizes="50x50"> -->
        <link rel="icon" href="favicon.webp" type="image/webp" sizes="50x50">

        <!-- Revisar: https://css-tricks.com/essential-meta-tags-social-media/ -->
        <meta name="author" content="Inoro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="description" content="<?php echo $description; ?>">
        <meta property="og:locale" content="es_ES" />
        <meta property="og:type" content="article" />
        <meta property="og:title" content="<?php echo $title; ?>" />
        <meta property="og:description" content="<?php echo $description; ?>" />
        <meta property="og:url" content="<?php echo get_url(true); ?>" />
        <meta property="og:site_name" content="record.rat.la" />
        <meta property="og:image" content="<?php echo get_url(false) . "/" . $article_img; ?>" />
        <meta name="twitter:card" content="summary_large_image" />
        <!-- <meta property="article:author" content="idex.php?q=202009180002i-inoro.html" /> -->
        <!-- <meta property="article:published_time" content="2020-09-21T00:04:15+00:00" /> -->
        <!-- <meta property="article:modified_time" content="2020-09-21T07:23:04+00:00" /> -->
        <meta name="twitter:creator" content="@0x12Faab7" />
        <!-- <meta name="twitter:site" content="cuenta_del_sitio" /> -->
        <meta name="robots" content="index, follow" />
        <!-- <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" /> -->
        <!-- <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" /> -->
        <!-- <link rel="canonical" href="<?php echo get_url(true); ?>" /> -->

        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-179041248-1"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'UA-179041248-1');
        </script>

        <style>
            body {
                background-color: <?php echo $colors[$color_id]["background"]; ?>;
                color: <?php echo $colors[$color_id]["text"]; ?>;
                font-size: 12pt; /*1.35em, 14pt*/
                font-family: Helvetica, sans-serif;
                /*font-family: Times, Serif;*/ /*Considerar obviar la letra Times y poner todo Serif*/
            }

            header, footer, p.center {text-align: center;}

            div#content {
                max-width: 750px;
                text-align: justify;
                text-justify: inter-word;
            }

            div#content, header img {
                width: 100%;
                margin: 0px auto;
            }

            h1, h2, h3, h4, h5, h6 {color: <?php echo $colors[$color_id]["title"]; ?>;}

            /* a {text-decoration: none;} */
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?php echo $colors[$color_id]["link"]; ?>;}
            a:visited {color: <?php echo $colors[$color_id]["link_visited"]; ?>;}
            a:active {color: <?php echo $colors[$color_id]["link_active"]; ?>;}

            pre {
                padding: 10px;
                overflow: auto;
            }

            code {padding: 1px;}

            pre, code {
                background-color: <?php echo $colors[$color_id]["code_background"]; ?>;
                color: <?php echo $colors[$color_id]["code_text"]; ?>;
            }

            img {width: 100%;}
            img.half {width: 50%;}
            header img {max-width: 400px;}
        </style>
    </head>

    <body>
        <header>
            <h1>record.rat.la</h1>
            <!-- Para evitar que el contenido se mueva al cargar la imagen puse "height: 209px;" al <p>. -->
            <p style="height: 210px;">
                <a href="https://www.instagram.com/pepunto.reik" title="Artista: @pepunto.reik">
                    <img src="img/rat<?php echo $colors[$color_id]["hedaer_img_color"]; ?>.svg" alt="Imagen del header, rata cantando: lalala." width="400" height="210">
                </a>
            </p>
            <!-- Licencia de la imagen -->
            <script type="application/ld+json">
                {
                    "@context": "https://schema.org/",
                    "@type": "ImageObject",
                    "contentUrl": "https://record.rat.la/img/rat<?php echo $colors[$color_id]["hedaer_img_color"]; ?>.svg",
                    "license": "https://creativecommons.org/licenses/by-nc-sa/4.0/",
                    "acquireLicensePage": "https://record.rat.la/index.php?q=202009180001i-faq.html"
                }
            </script>
            <p>
                <a href="index.php" title="Los últimos artículos.">reciente</a> / <a href="index.php?q=h" title="Todos los artículos ordenados por fecha.">histórico</a> / <a href="index.php?q=202009180001i-faq.html" title="¿Qué es esta página?">faq</a> / <a href="index.php?q=202009180003i-color.html" title="Cambia la paleta de colores para leer mejor.">color</a><br>
                <small>Esta página guarda una <a href="index.php?q=202009192256i-cookie.html" title="¡Infórmate!">cookie</a> para funcionar con normalidad</small><!-- ¿Debería acortar el mensaje? -->
            </p>
        </header>

        <div id="content">
            <?php
                $filenames = get_filenames($directory);
                switch ($action) {
                    case 0:
                        print_reciente($directory, $filenames, $articles_to_show);
                        echo "<br><p class=\"center\"><a href=\"index.php?q=h\">Más artículos</a></p>";
                        break;
                    case 1:
                        print_historico($directory, $filenames);
                        break;
                    case 2:
                        print_article($directory, "202009180003i-color.html");
                        break;
                    case 3:
                        print_article($directory, $_GET["q"]);
                        echo "<br><p class=\"center\"><a href=\"index.php?q=h\">Más artículos</a></p>";
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
                <a rel="license" href="https://creativecommons.org/licenses/by-nc-sa/4.0/" title="Todo el contenido multimedia está bajo la licencia CC-BY-NC-SA.">
                    <img alt="Licencia de Creative Commons BY-NC-SA" style="border-width: 0; width: auto;" src="img/cc.png" width="80" height="15"/>
                </a>
            </p>
        </footer>
    </body>
</html>
