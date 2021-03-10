<!-- 
               _     _
     _ __ __ _| |_  | | __ _
    | '__/ _` | __| | |/ _` |
    | | | (_| | |_ _| | (_| |
    |_|  \__,_|\__(_)_|\__,_|

    record.rat.la by Inoro (github.com/1noro)

 -->
<?php
    // Creamos u obtenemos la cookie funcional que guarda las preferencias del usuario (la paleta de colores)
    session_start();
    // Si se entra por primera vez a la web se guarda un cookie de sesión con las preferencias por defecto
    if (!isset($_SESSION["COLOR_ID"])) {
        $_SESSION["COLOR_ID"] = 0;
    }
    if (!isset($_SESSION["TEXT_SIZE_ID"])) {
        $_SESSION["TEXT_SIZE_ID"] = 0;
    }

    // --- Viariables globales ---
    $ARTICLES_TO_SHOW = 2; // número de artículos a mostrar en la página principal
    $DIRECTORY = 'pages/'; // carpeta donde se guardan los artículos
    $TITLE = "Reciente - record.rat.la"; // título de la página por defecto
    $DESCRIPTION = "Blog/web personal donde iré registrando mis proyectos y mis líos mentales."; // Descripción de la página por defecto.
    $ARTICLE_IMG = "img/article_default_img_white.webp"; // Imagen del artículo por defecto.

    $AUTHORS = [
        "a" => ["Anon", "404.html"], // Autor por defecto de los artículos anónimos
        "i" => ["Inoro", "inoro.html"]
    ];

    $TEXT_SIZES = [
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

    $COLORS = [
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
            "header_img_color" => "B"
        ],
        // Melocotón
        [
            "background" => "#EDD1B0", // Peach: #EDD1B0, Orange: #EDDD6E, Yellow: #F8FD89, 4chan: #FFFFEE
            "text" => "#000000",
            "title" => "#000000",
            "link" => "#0000EE",
            "link_visited" => "#551A8B",
            "link_active" => "#EE0000",
            "code_background" => "#FFFFEE", // #dfdebe, #f8bba5
            "code_text" => "inherit",
            "header_img_color" => "B"
        ],
        // Modo oscuro
        [
            "background" => "#000000",
            "text" => "#FFFFFF",
            "title" => "#FFFFFF",
            "link" => "#20B2AA", // #FFFF00
            "link_visited" => "#7FB5B5", // #CCCC00
            "link_active" => "#0000FF",
            "code_background" => "#FFFFFF",
            "code_text" => "#000000",
            "header_img_color" => "W"
        ],
        // Auto
        [
            "background" => "auto",
            "text" => "auto",
            "title" => "auto",
            "link" => "auto",
            "link_visited" => "auto",
            "link_active" => "auto",
            "code_background" => "auto",
            "code_text" => "auto",
            "header_img_color" => "B"
        ],
        // N-O-D-E
        [
            "background" => "#222222",
            "text" => "#C8C8C8",
            "title" => "#FFFFFF",
            "link" => "#FFFFFF",
            "link_visited" => "#FFFFFF",
            "link_active" => "#FFFFFF",
            "code_background" => "#1F1F1F",
            "code_text" => "#C8C8C8",
            "header_img_color" => "W"
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

    // add_page_if_exists, devuelve el parámetro de query "page" para concatenar a un enlace, si este está definido
    function add_page_if_exists() {
        if (isset($_GET["page"])) {
            return "&page=" . $_GET["page"];
        } else {
            return "";
        }
    }

    // normalize_line, devuelve el contenido de una linea sin espacios ni salto de linea
    function normalize_line($line) {
        return trim(str_replace("\n", "", $line));
    }

    // --- Obtención de datos de los artículos ---
    // get_filenames, obtiene los nombres de los artículos en la carpeta de los artículos
    function get_filenames($DIRECTORY) {
        $FILENAMES = array();
        $directory_obj = opendir($DIRECTORY);
        while(false != ($filename = readdir($directory_obj))) {
            if(($filename != ".") && ($filename != "..")) {
                $FILENAMES[] = $filename; // put in array
            }
        }
        return $FILENAMES;
    }

    // get_date_by_line, obtiene la fecha de un articulo en base al comentario de la primera línea del artículo
    function get_date_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<!-- ", "", $line);
        $line = str_replace(" -->", "", $line);

        $year = substr($line, 0, 4);
        $month = substr($line, 4, 2);
        $day = substr($line, 6, 2);
        $hour = substr($line, 8, 2);
        $minute = substr($line, 10, 2);

        return $year."/".$month."/".$day." ".$hour.":".$minute;
    }

    // get_author_data_by_line, obtiene los datos del autor en base a su alias en el comentario de la primera línea del artículo
    function get_author_data_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<!-- ", "", $line);
        $line = str_replace(" -->", "", $line);

        $authorid = substr($line, 12, 1);

        if (array_key_exists($authorid, $GLOBALS["AUTHORS"])) {
            $result = $GLOBALS["AUTHORS"][$authorid];
        } else {
            $result = $GLOBALS["AUTHORS"]["a"];
        }

        return $result;
    }

    // get_title_by_line, obtiene el título del artículo en base a la segunda línea de una artículo
    function get_title_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<h1>", "", $line);
        $line = str_replace("</h1>", "", $line);
        
        // quitamos las tags HTML y luego cambiamos los caracteres especiales por sus códigos HTML (incluidas las " y ')
        return htmlentities(strip_tags($line), ENT_QUOTES); 
    }

    // get_file_info, obtiene en formato diccionario el nombre del archivo, fecha, autor y título de un artículo
    function get_file_info($filename) {
        global $DIRECTORY;
        $filepath = $DIRECTORY . $filename;

        $file_obj = fopen($filepath, "r");
        $line1 = fgets($file_obj); // leemos la primera linea
        $line2 = fgets($file_obj); // leemos la segunda linea
        fclose($file_obj);

        return [
            "filename" => $filename,
            "datetime" => get_date_by_line($line1),
            "author_data" => get_author_data_by_line($line1),
            "title" => get_title_by_line($line2)
        ];
    }

    // get_description, obtiene el contenido del primer párrafo <p></p> del artículo y lo coloca como description del mismo
    // TODO: optimizar (sacar de lo que se carga en el main)
    function get_description($filepath) {
        $html = file_get_contents($filepath);
        $start = strpos($html, '<p>');
        $end = strpos($html, '</p>', $start);
        $paragraph = strip_tags(substr($html, $start, $end - $start + 4));
        $paragraph = str_replace("\n", "", $paragraph);
        return trim($paragraph);
    }

    // get_article_img, obtiene la primera imagen mostrada en el artículo
    // TODO: optimizar (sacar de lo que se carga en el main)
    function get_article_img($filepath) {
        $html = file_get_contents($filepath);
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $html, $image);
        if (isset($image['src'])) {
            $src = $image['src'];
        } else {
            $src = $GLOBALS["ARTICLE_IMG"];
        }
        return $src;
    }

    function get_sorted_file_info() {
        global $DIRECTORY, $FILENAMES;
        // creamos $file_info_arr y $datetime_arr previamente para ordenar los archivos por fecha
        $file_info_arr = array();
        $datetime_arr = array();
        foreach($FILENAMES as $filename) {
            $file_info = get_file_info($filename);
            array_push($file_info_arr, $file_info);
            array_push($datetime_arr, $file_info["datetime"]);
        }

        // en base a los dos arrays anteriores ordeno todo por fecha
        array_multisort($datetime_arr, SORT_DESC, $file_info_arr);

        return $file_info_arr;
    }

    // --- Impresión de contenidos ---
    // print_reciente, imprime la página de artículos recientes
    function print_reciente() {
        global $ARTICLES_TO_SHOW;
        $file_info_arr = get_sorted_file_info();

        $i = 1;
        foreach($file_info_arr as $file_info) {
            print_article($file_info["filename"]);
            if ($i >= $ARTICLES_TO_SHOW) {break;}
            echo "<hr>\n";
            $i++;
        }
    }

    // print_history, imprime la página del histórico de artículos ordenados por fecha DESC
    function print_history() {
        global $DIRECTORY, $FILENAMES;

        $file_info_arr = get_sorted_file_info();

        echo "<h1>Histórico de artículos</h1>\n";
        echo "<ul>\n";
        foreach($file_info_arr as $file_info) {
            printf(
                '<li><a href="index.php?page=%s">%s</a> (%s) %s</li>' . "\n",
                $file_info["filename"],
                $file_info["datetime"],
                $file_info["author_data"][0],
                $file_info["title"]
            );
        }
        echo "</ul>\n";
        printf("<p>Hay un total de %d artículos en la web.</p>\n", count($FILENAMES));
    }

    // print_article, imprime la página de un artículo cuyo nombre de archivo se pasa como parámetro
    function print_article($filename) {
        global $DIRECTORY;
        $filepath = $DIRECTORY . $filename;
        $file_info = get_file_info($filename);
        echo file_get_contents($filepath) . "\n";
        printf(
            '<p style="text-align:right;"><small><a href="index.php?page=%s" aria-label="Página del autor %s.">%s</a> - %s</small></p>' . "\n",
            $file_info["author_data"][1],
            $file_info["author_data"][0],
            $file_info["author_data"][0],
            $file_info["datetime"]
        );
        printf(
            '<p style="text-align:right;"><small><a href="index.php?page=%s" aria-label="Enlace al artículo, %s, para verlo individualmente.">Enlace al artículo</a></small></p>' . "\n",
            $filename,
            strtolower($file_info["title"])
        );
    }

    // --- Lógica de impresión ---
    // procesamos la variable GET "page" y obramos en consecuencia
    $ACTION = 0;
    $FILENAMES = get_filenames($DIRECTORY);
    if (isset($_GET["page"])) {
        if ($_GET["page"] == "history") {
            // Histórico
            $ACTION = 1;
            $TITLE = "Histórico de artículos - record.rat.la";
            $DESCRIPTION = "Listado de todos los artículos publicados en record.rat.la.";
        } elseif ($_GET["page"] == "color" && isset($_GET["id"])) {
            // Cambio de paleta de colores
            if ($_GET["id"] >= 0 && $_GET["id"] < count($COLORS)) {
                $_SESSION["COLOR_ID"] = $_GET["id"];
            }
            $ACTION = 2;
            $file_info = get_file_info("color.html");
            $TITLE = $file_info["title"] . " - record.rat.la";
        } else {
            if (in_array($_GET["page"], $FILENAMES)) {
                // Artículo
                $ACTION = 3;
                $filename = $_GET["page"];
                $file_info = get_file_info($filename);
                $TITLE = $file_info["title"] . " - record.rat.la";
                $DESCRIPTION = get_description($DIRECTORY . $filename);
                $ARTICLE_IMG = get_article_img($DIRECTORY . $filename);
            } else {
                // Error 404
                $ACTION = 404;
                $file_info = get_file_info("404.html");
                $TITLE = $file_info["title"] . " - record.rat.la";
            }
        }
    }

    if (isset($_GET["size"])) {
        // Cambio de tamaño de texto
        if ($_GET["size"] >= 0 && $_GET["size"] < count($TEXT_SIZES)) {
            $_SESSION["TEXT_SIZE_ID"] = $_GET["size"];
        }
    }

    $COLOR_ID = $_SESSION["COLOR_ID"];
    $TEXT_SIZE_ID = $_SESSION["TEXT_SIZE_ID"];
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $TITLE; ?></title>
        <link rel="icon" href="favicon.webp" type="image/webp" sizes="50x50">

        <!-- Avisamos al navegador de que se prepare para hacer una petición a los siguientes dominios -->
        <link rel="preconnect dns-prefetch" href="https://www.googletagmanager.com">
        <link rel="preconnect dns-prefetch" href="https://www.google-analytics.com">

        <!-- Revisar: https://css-tricks.com/essential-meta-tags-social-media/ -->
        <meta name="author" content="Inoro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="description" content="<?php echo $DESCRIPTION; ?>">
        <meta property="og:locale" content="es_ES" />
        <meta property="og:type" content="article" />
        <meta property="og:title" content="<?php echo $TITLE; ?>" />
        <meta property="og:description" content="<?php echo $DESCRIPTION; ?>" />
        <meta property="og:url" content="<?php echo get_url(true); ?>" />
        <meta property="og:site_name" content="record.rat.la" />
        <meta property="og:image" content="<?php echo get_url(false) . "/" . $ARTICLE_IMG; ?>" />
        <meta name="twitter:card" content="summary_large_image" />
        <!-- <meta property="article:author" content="idex.php?page=inoro.html" /> -->
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
        <!-- La carga del script interno se hace después de los estilos para mejorar la performance -->
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'UA-179041248-1');
        </script>

        <style>
            body {
                background-color: <?php echo $COLORS[$COLOR_ID]["background"]; ?>;
                color: <?php echo $COLORS[$COLOR_ID]["text"]; ?>;
                font-size: <?php echo $TEXT_SIZES[$TEXT_SIZE_ID]["text"]; ?>; /* 1.35em, 14pt */
                /* font-family: Times, Serif; */
                font-family: Helvetica, sans-serif;
            }

            /* --- Enlaces --- */
            a.text_size_link {text-decoration: none;}
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?php echo $COLORS[$COLOR_ID]["link"]; ?>;}
            a:visited {color: <?php echo $COLORS[$COLOR_ID]["link_visited"]; ?>;}
            a:active {color: <?php echo $COLORS[$COLOR_ID]["link_active"]; ?>;}

            /* --- Contenedores HEADER y FOOTER --- */
            header, footer, p.center {text-align: center;}

            header div#web_title {
                font-size: 1.9em; /* este valor multiplica al valor definido en el body */
                font-weight: bold;
                margin: 16px 0px; /* porque es un <div> y no un <p> */
            }

            header img {
                width: auto;
                max-width: 400px;
            }

            header p#web_nav {font-size: 1.4em;} /* este valor multiplica al valor definido en el body */

            /* --- contenedor MAIN --- */
            main {
                max-width: 750px;
                /* text-align: justify;
                text-justify: inter-word; */
                margin: 0 auto;
            }

            h1, h2, h3, h4, h5, h6 {
                color: <?php echo $COLORS[$COLOR_ID]["title"]; ?>;
                text-align: left;
            }

            pre {
                padding: 10px;
                overflow: auto;
            }

            code {padding: 1px;}

            pre, code {
                background-color: <?php echo $COLORS[$COLOR_ID]["code_background"]; ?>;
                color: <?php echo $COLORS[$COLOR_ID]["code_text"]; ?>;
            }

            pre, code, samp {font-size: <?php echo $TEXT_SIZES[$TEXT_SIZE_ID]["code"]; ?>; /* 1.1em */}

            img {width: 100%;} /* todas las imágenes menos la del header */

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
        
        <header id="header" role="banner" aria-label="Cabecera" tabindex="-1">
            <nav role="navigation" aria-label="Enlaces de control de la web" style="text-align: left;">
                <a class="text_size_link" style="font-size: 1.05em;" href="index.php?size=0<?php echo add_page_if_exists(); ?>" aria-label="a, texto a tamaño por defecto.">a</a> 
                <a class="text_size_link" style="font-size: 1.20em;" href="index.php?size=1<?php echo add_page_if_exists(); ?>" aria-label="a, texto a tamaño grande.">a</a> 
                <a class="text_size_link" style="font-size: 1.35em;" href="index.php?size=2<?php echo add_page_if_exists(); ?>" aria-label="a, texto a tamaño enorme.">a</a> / 
                <a href="#main">ir al artículo</a> / 
                <a href="#footer">ir al pié</a>
            </nav>
            <!-- Título H1 de la web -->
            <div id="web_title">record.rat.la</div>
            <!-- Para evitar que el contenido se mueva al cargar la imagen puse "height: 180.47px;" al <p>. -->
            <p><!-- style="height: 180.47px;" -->
                <a href="https://www.instagram.com/pepunto.reik" aria-label="Artista: @pepunto.reik">
                    <img src="img/rat<?php echo $COLORS[$COLOR_ID]["header_img_color"]; ?>.webp" alt="Logotipo de la web, una rata cantando: la la la." width="400" height="180">
                </a>
                <!-- Licencia de la imagen -->
                <script type="application/ld+json">
                    {
                        "@context": "https://schema.org/",
                        "@type": "ImageObject",
                        "contentUrl": "https://record.rat.la/img/rat<?php echo $COLORS[$COLOR_ID]["header_img_color"]; ?>.svg",
                        "license": "https://creativecommons.org/licenses/by-nc-sa/4.0/",
                        "acquireLicensePage": "https://record.rat.la/index.php?page=faq.html"
                    }
                </script>
            </p>
            <nav role="navigation" aria-label="Enlaces a las secciones de la página">
                <p id="web_nav">
                    <a href="index.php" aria-label="Artículos recientes.">reciente</a> / 
                    <a href="index.php?page=history" aria-label="El histórico de artículos ordenados por fecha.">histórico</a> / 
                    <a href="index.php?page=faq.html" aria-label="Preguntas frecuentes sobre esta página (faq).">faq</a> / 
                    <a href="index.php?page=color.html" aria-label="Cambia la paleta de colores para leer mejor o para molar más.">color</a>
                </p>
            </nav>
            <p>
                <small>
                    <!-- ¿Debería acortar el mensaje? -->
                    Esta página guarda una <a href="index.php?page=cookie.html" aria-label="¡Infórmate sobre las cookies!">cookie</a> funcional para el estilo y cuatro analíticas para google
                </small>
            </p>
        </header>

        <main id="main" role="main" aria-label="Contenido principal" tabindex="-1">
<?php
    // Imprimimos lo indicado por la variable $ACTION en el <main>
    switch ($ACTION) {
        case 0:
            print_reciente();
            break;
        case 1:
            print_history();
            break;
        case 2:
            print_article("color.html");
            break;
        case 3:
            print_article($_GET["page"]);
            break;
        case 404:
            print_article("404.html");
            break;
    }
?>
        </main>

        <footer id="footer" role="contentinfo" aria-label="Licencias y contactos" tabindex="-1">
            <nav role="navigation" aria-label="Enlace al histórico de artículos">
                <p class="center">
                    <a href="index.php?page=history">[Más artículos]</a>
                </p>
            </nav>
            <nav role="navigation" aria-label="Moverse por esta página">
                <p>
                    <a href="#header">ir arriba</a> / <a href="#main">ir al artículo</a>
                </p>
            </nav>
            <nav role="navigation" aria-label="Enlaces de contacto">
                <p>
                    <a href="https://github.com/1noro">github</a> / 
                    <a href="https://gitlab.com/1noro">gitlab</a> / 
                    <a href="https://twitter.com/0x12Faab7">twiter</a> / 
                    <a href="mailto:ppuubblliicc@protonmail.com">mail</a> (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" aria-label="¡Mándame un correo cifrado con gpg!">gpg</a>)
                </p>
            </nav>
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
