<?php
    // record.rat.la by Inoro (https://github.com/1noro/record.rat.la)

    // Creamos u obtenemos la cookie funcional que guarda las preferencais del usuario (la paleta de colores)
    session_start();
    // Si se entra por primera vez a la web se guarada un cookie de sesión con las preferencias por defecto
    if (!isset($_SESSION["color_id"])) {
        $_SESSION["color_id"] = 0;
    }
    if (!isset($_SESSION["text_size_id"])) {
        $_SESSION["text_size_id"] = 0;
    }

    $articles_to_show = 2; // número de artículos a mostrar en la página principal
    $directory = 'articles/'; // carpeta donde se guardan los artículos
    $title = "Reciente - record.rat.la"; // título de la página por defecto
    $description = "Blog/web personal donde iré registrando mis proyectos y mis líos mentales."; // Descripción de la página por defecto.
    $article_img = "img/article_default_img_white.webp"; // Imagen del artículo por defecto.

    $authors = [
        "a" => ["Anon", "202009180000i-404.html"], // Autor por defecto de los artículos anónimos
        "i" => ["Inoro", "202009180002i-inoro.html"]
    ];

    $text_sizes = [
        [
            "text" => "1.05em",
            "code" => "1.1em"
        ],
        [
            "text" => "1.2em",
            "code" => "1.25em"
        ],
        [
            "text" => "1.35em",
            "code" => "1.4em"
        ]
    ];

    $colors = [
        // Paleta de colores por defecto 0 (B&W)
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
            "link" => "#FFFF00",
            "link_visited" => "#CCCC00",
            "link_active" => "#0000FF",
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
            "title" => "#FFFFFF",
            "link" => "#FFFFFF",
            "link_visited" => "#FFFFFF",
            "link_active" => "#FFFFFF",
            "code_background" => "#1F1F1F",
            "code_text" => "#C8C8C8",
            "hedaer_img_color" => "W"
        ]
    ];

    // --- Utilidades genéricas ---
    // get_url, monta la URL de la página para imprimirla en los headers HTML  en base a la URL dada por el usuario
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

    // add_q_if_exists, devuelve el parametro de query "q" para concatenar a un enlace, si este está definido
    function add_q_if_exists() {
        if (isset($_GET["q"])) {
            return "&q=" . $_GET["q"];
        } else {
            return "";
        }
    }

    // --- Obtención de datos de los artículos ---
    // get_filenames, obtiene los nombres de los artículos en la carpeta articles
    function get_filenames($directory) {
        $files = array();
        $directory_obj = opendir($directory);
        while(false != ($filename = readdir($directory_obj))) {
            if(($filename != ".") && ($filename != "..")) {
                $filenames[] = $filename; // put in array
            }
        }
        natsort($filenames); // ordenamos alfabeticamente
        $filenames = array_reverse($filenames); // le damos la vuelta a la ordenación anterior
        return $filenames;
    }

    // get_date, obtiene la fecha de un articulo en función de su nombre
    function get_date($filename) {
        $year = substr($filename, 0, 4);
        $month = substr($filename, 4, 2);
        $day = substr($filename, 6, 2);
        $hour = substr($filename, 8, 2);
        $minute = substr($filename, 10, 2);
        $result = $year."/".$month."/".$day." ".$hour.":".$minute;
        return $result;
    }

    // get_author_data, obtiene los datos del autor en base a su alias en el nombre del artículo
    function get_author_data($filename) {
        $authorid = substr($filename, 12, 1);
        if (array_key_exists($authorid, $GLOBALS["authors"])) {
            $result = $GLOBALS["authors"][$authorid];
        } else {
            $result = $GLOBALS["authors"]["a"];
        }
        return $result;
    }

    // get_title, obtiene el título del artículo en base al texto en el primer <h1></h1> encontrado
    function get_title($filepath) {
        $file_obj = fopen($filepath, "r");
        $result = fgets($file_obj);
        $result = str_replace("<h1>", "", $result);
        $result = str_replace("</h1>", "", $result);
        $result = str_replace("\n", "", $result);
        fclose($file_obj);
        // quitamos las tags HTML y luego cambiamos los caracteres especiales por sus códigos HTML (incluidas las " y ')
        return htmlentities(strip_tags($result), ENT_QUOTES); 
    }

    // get_description, obtiene el contenido del primer párrafo <p></p> del artículo y lo coloca como description del mismo
    function get_description($filepath) {
        $html = file_get_contents($filepath);
        $start = strpos($html, '<p>');
        $end = strpos($html, '</p>', $start);
        $paragraph = strip_tags(substr($html, $start, $end - $start + 4));
        $paragraph = str_replace("\n", "", $paragraph);
        return trim($paragraph);
    }

    // get_article_img, obtiene la primera imagen mostrada en el artículo
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

    // --- Impresión de contenidos ---
    // print_reciente, imprime la página de artículos recientes
    function print_reciente($directory, $filenames, $articles_to_show) {
        $i = 1;
        foreach($filenames as $filename) {
            print_article($directory, $filename);
            if ($i >= $articles_to_show) {break;}
            echo "<hr>";
            $i++;
        }
    }

    // print_historico, imprime l apágina del histórico de artículos
    function print_historico($directory, $filenames) {
        echo "<h1>Histórico de artículos</h1>";
        echo "<ul>";
        foreach($filenames as $filename) {
            echo "<li><a href=\"index.php?q=" . $filename . "\">" . get_date($filename) . "</a> (" . get_author_data($filename)[0] . ") " . get_title($directory . $filename) . "</li>";
        }
        echo "</ul>";
        echo "<p>Hay un total de " . count($filenames) . " artículos en la web.</p>";
    }

    // print_article, imprime la tágina de un artículo pasado como parámetro
    function print_article($directory, $filename) {
        echo file_get_contents($directory . $filename);
        echo "<p style=\"text-align:right;\"><a href=\"index.php?q=" . get_author_data($filename)[1] . "\" aria-label=\"Página del autor " . get_author_data($filename)[0] . ".\" aria-label=\"Página del autor.\">" . get_author_data($filename)[0] . "</a> - " . get_date($filename) . "</small></p>";
        echo "<p style=\"text-align:right;\"><small><a href=\"index.php?q=" . $filename . "\" aria-label=\"Enlace al artículo '" . get_title($directory . $filename) . "' para verlo individualmente.\">Enlace al artículo</a></p>";
    }

    // procesamos la variable GET "q" y obramos en consecuencia
    $action = 0;
    $filenames = get_filenames($directory);
    if (isset($_GET["q"])) {
        if ($_GET["q"] == "h") {
            // Histórico
            $action = 1;
            $title = "Histórico de artículos - record.rat.la";
            $description = "Listado de todos los artículos publicados en record.rat.la.";
        } elseif ($_GET["q"] == "c" && isset($_GET["c"])) {
            // Cambio de paleta de colores
            if ($_GET["c"] >= 0 && $_GET["c"] < count($colors)) {
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
                // Error 404
                $action = 404;
                $title = get_title($directory . "202009180000i-404.html") . " - record.rat.la";
            }
        }
    }

    if (isset($_GET["size"])) {
        // Cambio de tamaño de texto
        if ($_GET["size"] >= 0 && $_GET["size"] < count($text_sizes)) {
            $_SESSION["text_size_id"] = $_GET["size"];
        }
    }

    $color_id = $_SESSION["color_id"];
    $text_size_id = $_SESSION["text_size_id"];
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $title; ?></title>
        <link rel="icon" href="favicon.webp" type="image/webp" sizes="50x50">

        <!-- Avisamos al navegador de que se prepare para hacer una petición a estes dominios -->
        <link rel="preconnect dns-prefetch" href="https://www.googletagmanager.com">
        <link rel="preconnect dns-prefetch" href="https://www.google-analytics.com">

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
        <!-- La carga del Scrit interno se hace después de los estilos para mejorar la performance -->
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
                font-size: <?php echo $text_sizes[$text_size_id]["text"]; ?>; /* 1.35em, 14pt */
                font-family: Helvetica, sans-serif;
                /* font-family: Times, Serif; */ /* Considerar obviar la letra Times y poner todo Serif */
            }

            /* --- Enlaces --- */
            a.text_size_link {text-decoration: none;}
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?php echo $colors[$color_id]["link"]; ?>;}
            a:visited {color: <?php echo $colors[$color_id]["link_visited"]; ?>;}
            a:active {color: <?php echo $colors[$color_id]["link_active"]; ?>;}

            /* --- Contenedores HEADER y FOOTER --- */
            header, footer, p.center {text-align: center;}

            header p#web_title {
                font-size: 1.9em; /* este valor multiplica al valor definido en el body */
                font-weight: bold;
            }

            header img {max-width: 400px;}
            header p#web_nav {font-size: 1.4em;} /* este valor multiplica al valor definido en el body */

            /* --- contenedor MAIN --- */
            main {
                max-width: 750px;
                text-align: justify;
                text-justify: inter-word;
                margin: 0 auto;
            }

            h1, h2, h3, h4, h5, h6 {
                color: <?php echo $colors[$color_id]["title"]; ?>;
                text-align: left;
            }

            pre {
                padding: 10px;
                overflow: auto;
            }

            code {padding: 1px;}

            pre, code {
                background-color: <?php echo $colors[$color_id]["code_background"]; ?>;
                color: <?php echo $colors[$color_id]["code_text"]; ?>;
            }

            pre, code, samp {font-size: <?php echo $text_sizes[$text_size_id]["code"]; ?>; /* 1.1em */}

            img :not(header img) {width: 100%;} /* todas las imágenes menos la del header */

            img.half {
                width: 50%;
                display: block;
                margin: 0 auto;
            }
        </style>

        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La situo aquí para mejorar la carga de la web -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-179041248-1"></script>
    </head>

    <body>
        
        <header id="header" role="banner" aria-label="Cabecera" >
            <nav role="navigation" aria-label="Enlaces de control de la web" style="text-align: left;">
                <a class="text_size_link" style="font-size: 1.05em;" href="index.php?size=0<?php echo add_q_if_exists(); ?>" aria-label="a, texto a tamaño por defecto.">a</a> 
                <a class="text_size_link" style="font-size: 1.20em;" href="index.php?size=1<?php echo add_q_if_exists(); ?>" aria-label="a, texto a tamaño grande.">a</a> 
                <a class="text_size_link" style="font-size: 1.35em;" href="index.php?size=2<?php echo add_q_if_exists(); ?>" aria-label="a, texto a tamaño enorme.">a</a> / 
                <a href="#main">ir al artículo</a> / 
                <a href="#footer">ir al pié</a>
            </nav>
            <!-- Título H1 de la web -->
            <p id="web_title">record.rat.la</p>
            <!-- Para evitar que el contenido se mueva al cargar la imagen puse "height: 180.47px;" al <p>. -->
            <p><!-- style="height: 180.47px;" -->
                <a href="https://www.instagram.com/pepunto.reik" aria-label="Artista: @pepunto.reik">
                    <img src="img/rat<?php echo $colors[$color_id]["hedaer_img_color"]; ?>.webp" alt="Logotipo de la web, una rata cantando: la la la." width="400" height="180.47">
                </a>
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
            </p>
            <nav role="navigation" aria-label="Enlaces a las secciones de la página">
                <p id="web_nav">
                    <a href="index.php" aria-label="Artículos recientes.">reciente</a> / 
                    <a href="index.php?q=h" aria-label="Ver el histórico de artículos ordenados por fecha.">histórico</a> / 
                    <a href="index.php?q=202009180001i-faq.html" aria-label="faq, preguntas frecuentes sobre esta página.">faq</a> / 
                    <a href="index.php?q=202009180003i-color.html" aria-label="Cambia la paleta de colores para leer mejor o para molar más.">color</a>
                </p>
            </nav>
            <p>
                <small>
                    <!-- ¿Debería acortar el mensaje? -->
                    Esta página guarda una <a href="index.php?q=202009192256i-cookie.html" aria-label="¡Infórmate sobre las cookies!">cookie</a> para funcionar con normalidad
                </small>
            </p>
        </header>

        <main id="main" role="main" aria-label="Contenido principal">
            <?php
                $filenames = get_filenames($directory);
                switch ($action) {
                    case 0:
                        print_reciente($directory, $filenames, $articles_to_show);
                        // echo "<p class=\"center\"><a href=\"index.php?q=h\">[Más artículos]</a></p>";
                        break;
                    case 1:
                        print_historico($directory, $filenames);
                        break;
                    case 2:
                        print_article($directory, "202009180003i-color.html");
                        break;
                    case 3:
                        print_article($directory, $_GET["q"]);
                        // echo "<p class=\"center\"><a href=\"index.php?q=h\">[Más artículos]</a></p>";
                        break;
                    case 404:
                        print_article($directory, "202009180000i-404.html");
                        // echo "<p class=\"center\"><a href=\"index.php?q=h\">[Más artículos]</a></p>";
                        break;
                }
            ?>
            
        </main>

        <footer id="footer" role="contentinfo" aria-label="Licencias y contactos">
            <nav role="navigation" aria-label="Enlace al histórico de artículos.">
                <p class="center">
                    <a href="index.php?q=h">[Más artículos]</a>
                </p>
            </nav>
            <nav role="navigation" aria-label="Skip Links">
                <p>
                    <a href="#header">ir arriba</a> / <a href="#main">ir al artículo</a>
                </p>
            </nav>
            <p>
                <small>
                    <a href="https://github.com/1noro">github</a> / 
                    <a href="https://gitlab.com/1noro">gitlab</a> / 
                    <a href="https://twitter.com/0x12Faab7">twiter</a> / 
                    <a href="mailto:ppuubblliicc@protonmail.com">mail</a> (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" aria-label="¡Mándame un correo cifrado con gpg!">gpg</a>)
                </small>
            </p>
            <p>
                <small>
                    Creado por <a href="https://github.com/1noro/record.rat.la">Inoro</a> bajo la licencia <a href="LICENSE" aria-label="Todo el código que sustenta la web está bajo la licencia GPLv3.">GPLv3</a>
                </small>
            </p>
            <p>
                <a rel="license" href="https://creativecommons.org/licenses/by-nc-sa/4.0/" aria-label="Todo el contenido multimedia está bajo la licencia CC-BY-NC-SA.">
                    <img alt="Licencia de Creative Commons BY-NC-SA" style="border-width: 0; width: auto;" src="img/cc.png" width="80" height="15"/>
                </a>
            </p>
        </footer>
    </body>
</html>
