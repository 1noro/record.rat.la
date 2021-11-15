<!--
               _     _
     _ __ __ _| |_  | | __ _
    | '__/ _` | __| | |/ _` |
    | | | (_| | |_ _| | (_| |
    |_|  \__,_|\__(_)_|\__,_|

    record.rat.la by Inoro (github.com/1noro)

-->
<?php
    // Creamos u obtenemos la cookie funcional que guarda las preferencias 
    // del usuario (la paleta de colores)
    session_start();

    // Si se entra por primera vez a la web se guarda un cookie de sesión con 
    // las preferencias por defecto
    if (!isset($_SESSION["COLOR_ID"])) { $_SESSION["COLOR_ID"] = 0; }
    if (!isset($_SESSION["TEXT_SIZE_ID"])) { $_SESSION["TEXT_SIZE_ID"] = 0; }

    // --- Requested Values ---
    if (isset($_GET["page"])) { define("REQ_PAGE", $_GET["page"]); }
    if (isset($_GET["id"])) { define("REQ_COLOR_ID", intval($_GET["id"])); }
    if (isset($_GET["size"])) { define("REQ_SIZE_ID", intval($_GET["size"])); }

    // --- Constantes ---
    define("E404_PAGE", "404.html");
    define("COLOR_PAGE", "color.html");
    define("PAGES_TO_SHOW", 2); // número de páginas a mostrar en la portada, "reciente"
    define("DIRECTORY", "pages/"); // carpeta donde se guardan las páginas
    define("FILENAMES", get_filenames(DIRECTORY)); // obtenemos todas las páginas de la carpeta DIRECTORY
    
    define("DEF_TITLE_SUFFIX", " - record.rat.la"); // sufijo por defecto del título de la página
    define("DEF_TITLE", "Reciente" . DEF_TITLE_SUFFIX); // título por defecto de la página
    define("DEF_DESCRIPTION", "Blog/web personal donde iré registrando mis proyectos y mis líos mentales."); // descripción por defecto de la página
    define("DEF_PAGE_IMG", "img/article_default_img_white.jpg"); // imagen por defecto del artículo

    define("AUTHORS", [
        "a" => ["Anon", E404_PAGE], // autor por defecto
        "i" => ["Inoro", "inoro.html"]
    ]);

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
        // Paleta de colores por defecto 0 (G&W)
        [
            "background" => "#FFFFFF",
            "text" => "#222324",
            "title" => "#222324",
            "link" => "#0000EE",
            "link_visited" => "#551A8B",
            "link_active" => "#EE0000",
            "code_background" => "#dddcdb",
            "code_text" => "#222324",
            "header_img_color" => "B"
        ],
        // Melocotón
        [
            "background" => "#EDD1B0",
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
        ],
        // GitHub Dark dimmed
        [
            "background" => "#22272E",
            "text" => "#ADBAC7",
            "title" => "#ADBAC7",
            "link" => "#539BF5",
            "link_visited" => "#539BF5",
            "link_active" => "#539BF5",
            "code_background" => "#2b3139", // 25% mas claro que #22272E
            "code_text" => "#e4e9ed", // 25% mas claro que #ADBAC7
            "header_img_color" => "W"
        ],
        // B&W
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
        ]
    ];

    define("MONTHS", [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre"
    ]);

    // --- Utilidades genéricas ---

    // add_page_if_exists, devuelve el parámetro de query "page" para 
    // concatenar a un enlace, si este está definido
    function add_page_if_exists() {
        if (defined("REQ_PAGE")) {
            return "&page=" . REQ_PAGE;
        }
        return "";
    }

    // normalize_line, devuelve el contenido de una linea sin espacios ni 
    // salto de linea
    function normalize_line($line) {
        return trim(str_replace("\n", "", $line));
    }

    // reduce_h1, en base a un texto html dado reduce el valor de todos los 
    // tag <hN> en uno excepto el <h6>. El inconveniente es que los <h5> y 
    // los <h6> quedarán al mismo nivel
    function reduce_h1($html) {
        $html = str_replace("h5", "h6", $html);
        $html = str_replace("h4", "h5", $html);
        $html = str_replace("h3", "h4", $html);
        $html = str_replace("h2", "h3", $html);
        $html = str_replace("h1", "h2", $html);
        return $html;
    }

    // --- Obtención de datos de las páginas ---
    // get_filenames, obtiene los nombres de las páginas en la carpeta 
    // especificada
    function get_filenames($directory) {
        $filenames = array();
        $directoryObj = opendir($directory);
        while($filename = readdir($directoryObj)) {
            if(($filename != ".") && ($filename != "..")) {
                $filenames[] = $filename; // put in array
            }
        }
        return $filenames;
    }

    // get_date_by_line, obtiene la fecha de un artículo en base al 
    // comentario de la primera línea del artículo
    function get_date_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<!-- ", "", $line);
        $line = str_replace(" -->", "", $line);

        $year = substr($line, 0, 4);
        $month = substr($line, 4, 2);
        $day = substr($line, 6, 2);
        $hour = substr($line, 8, 2);
        $minute = substr($line, 10, 2);

        return [
            "datetime" => $year."/".$month."/".$day." ".$hour.":".$minute,
            "year" => $year,
            "month" => $month,
            "day" => $day,
            "hour" => $hour,
            "minute" => $minute
        ];
    }

    // get_author_data_by_line, obtiene los datos del autor en base a su 
    // alias en el comentario de la primera línea del artículo
    function get_author_data_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<!-- ", "", $line);
        $line = str_replace(" -->", "", $line);

        $authorId = substr($line, 12, 1);

        if (array_key_exists($authorId, AUTHORS)) {
            return AUTHORS[$authorId];
        }
        return AUTHORS["a"];
    }

    // get_title_by_line, obtiene el título del artículo en base a la 
    // segunda línea de una artículo
    function get_title_by_line($line) {
        $line = normalize_line($line);
        $line = str_replace("<h1>", "", $line);
        $line = str_replace("</h1>", "", $line);
        
        // quitamos las tags HTML y luego cambiamos los caracteres 
        // especiales por sus códigos HTML (incluidas las " y ')
        return htmlentities(strip_tags($line), ENT_QUOTES); 
    }

    // get_page_info, obtiene en formato diccionario el nombre del archivo, 
    // fecha, autor y título de un artículo
    function get_page_info($filename) {
        $filepath = DIRECTORY . $filename;

        $fileObj = fopen($filepath, "r");
        $line1 = fgets($fileObj); // leemos la primera linea
        $line2 = fgets($fileObj); // leemos la segunda linea
        fclose($fileObj);

        $datetimeInfo = get_date_by_line($line1);

        return [
            "filename" => $filename,
            "author_data" => get_author_data_by_line($line1),
            "title" => get_title_by_line($line2),
            "datetime" => $datetimeInfo["datetime"],
            "year" => $datetimeInfo["year"],
            "month" => $datetimeInfo["month"],
            "day" => $datetimeInfo["day"],
            "hour" => $datetimeInfo["hour"],
            "minute" => $datetimeInfo["minute"]
        ];
    }

    // get_description, obtiene el contenido del primer párrafo <p></p> del 
    // artículo y lo coloca como description del mismo
    // TODO: optimizar (sacar de lo que se carga en el main)
    function get_description($filepath) {
        $html = file_get_contents($filepath);
        $start = strpos($html, '<p>');
        $end = strpos($html, '</p>', $start);
        $paragraph = strip_tags(substr($html, $start, $end - $start + 4));
        $paragraph = str_replace("\n", "", $paragraph);
        // quitamos el exceso de espacios en blanco delante, atrás y en el medio
        $paragraph = preg_replace('/\s+/', ' ', trim($paragraph));
        // si la descripción es mayor a 160 caracteres es malo para el SEO
        if (strlen($paragraph) > 160) {
            $paragraph = mb_substr($paragraph, 0, 160 - 3) . "...";
        }
        return trim($paragraph);
    }

    // get_page_img, obtiene la primera imagen mostrada en el artículo
    // TODO: optimizar (sacar de lo que se carga en el main)
    function get_page_img($filepath) {
        $html = file_get_contents($filepath);
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $html, $image);
        if (isset($image["src"])) {
            return $image["src"];
        }
        return DEF_PAGE_IMG;
    }

    // get_sorted_file_info, ...
    function get_sorted_file_info() {
        // creamos $fileInfoArr y $datetimeArr previamente para ordenar 
        // los archivos por fecha
        $fileInfoArr = array();
        $datetimeArr = array();
        foreach(FILENAMES as $filename) {
            $fileInfo = get_page_info($filename);
            array_push($fileInfoArr, $fileInfo);
            array_push($datetimeArr, $fileInfo["datetime"]);
        }

        // en base a los dos arrays anteriores ordeno por fecha
        array_multisort($datetimeArr, SORT_DESC, $fileInfoArr);

        return $fileInfoArr;
    }

    // --- Impresión de contenidos ---
    // print_reciente, imprime la portada (las N páginas más recientes)
    function print_reciente() {
        $fileInfoArr = get_sorted_file_info();

        echo "<h1>Reciente</h1>\n<hr>\n";

        $number = 1;
        foreach($fileInfoArr as $fileInfo) {
            $page = $fileInfo["filename"];
            echo "<article>\n";
            print_page(reduce_h1(get_page_content($page)), get_page_info($page));
            echo "</article>\n";
            if ($number >= PAGES_TO_SHOW) {break;}
            echo "<hr>\n";
            $number++;
        }
    }

    // print_archive, imprime la página 'archivo', donde se listan las 
    // páginas ordenadas por fecha DESC
    function print_archive() {
        $currentYear = "";
        $currentMonth = "";

        $fileInfoArr = get_sorted_file_info();

        echo "<h1>Archivo</h1>\n";

        foreach($fileInfoArr as $fileInfo) {
            if ($currentYear != $fileInfo["year"]) {
                $currentYear = $fileInfo["year"];
                printf("<h2>%s</h2>\n<hr>\n", $fileInfo["year"]);
            }
            if ($currentMonth != $fileInfo["month"]) {
                $currentMonth = $fileInfo["month"];
                printf("<h3>%s</h3>\n", MONTHS[intval($fileInfo["month"]) - 1]);
            }
            printf(
                '<blockquote>%s %s:%s - <a href="index.php?page=%s">%s</a> - %s</blockquote>' . "\n",
                $fileInfo["day"],
                $fileInfo["hour"],
                $fileInfo["minute"],
                $fileInfo["filename"],
                $fileInfo["title"],
                $fileInfo["author_data"][0]
            );
        }

        printf("<p>Hay un total de %d páginas en la web.</p>\n", count(FILENAMES));
    }

    // get_page_content, todo...
    function get_page_content($filename) {
        return file_get_contents(DIRECTORY . $filename);
    }

    // print_page, imprime la página de un artículo cuyo nombre de archivo 
    // se pasa como parámetro
    function print_page($fileContent, $fileInfo) {
        echo $fileContent . "\n";
        printf(
            '<p style="text-align:right;"><small><a href="index.php?page=%s" aria-label="Página del autor %s.">%s</a> - %s</small></p>' . "\n",
            $fileInfo["author_data"][1],
            $fileInfo["author_data"][0],
            $fileInfo["author_data"][0],
            $fileInfo["datetime"]
        );
        printf(
            '<p style="text-align:right;"><small><a href="index.php?page=%s" aria-label="Enlace al contenido, %s, para verlo individualmente.">Enlace al contenido</a></small></p>' . "\n",
            $fileInfo["filename"],
            strtolower($fileInfo["title"])
        );
    }

    // --- Variables globales ---
    $TITLE = DEF_TITLE;
    $DESCRIPTION = DEF_DESCRIPTION; 
    $PAGE_IMG = DEF_PAGE_IMG;
    $ACTION = 0;

    // --- Montamos las variables URL y FULL_URL
    $URL = "http";
    if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on') {
        $URL = "https";
    }
    $URL .= "://";
    $URL .= $_SERVER["HTTP_HOST"];
    $FULL_URL = $URL . $_SERVER["REQUEST_URI"];

    // --- Lógica de impresión ---
    // procesamos REQ_PAGE y obramos en consecuencia
    if (defined("REQ_PAGE")) {
        if (REQ_PAGE == "archive") {
            // Archivo
            $ACTION = 1;
            $TITLE = "Archivo - record.rat.la";
            $DESCRIPTION = "Listado de todas las páginas publicadas en record.rat.la";
        } elseif (REQ_PAGE == COLOR_PAGE && defined("REQ_COLOR_ID")) {
            // Cambio de paleta de colores
            if (REQ_COLOR_ID >= 0 && REQ_COLOR_ID < count($COLORS)) {
                $_SESSION["COLOR_ID"] = REQ_COLOR_ID;
            }
            $ACTION = 2;
            $fileInfo = get_page_info(COLOR_PAGE);
            $TITLE = $fileInfo["title"] . DEF_TITLE_SUFFIX;
        } else {
            if (in_array(REQ_PAGE, FILENAMES)) {
                // Artículo
                $ACTION = 3;
                $filename = REQ_PAGE;
                $fileInfo = get_page_info($filename);
                $TITLE = $fileInfo["title"] . DEF_TITLE_SUFFIX;
                $DESCRIPTION = get_description(DIRECTORY . $filename);
                $PAGE_IMG = get_page_img(DIRECTORY . $filename);
            } else {
                // Error 404
                $ACTION = 404;
                $fileInfo = get_page_info(E404_PAGE);
                $TITLE = $fileInfo["title"] . DEF_TITLE_SUFFIX;
                http_response_code(404);
            }
        }
    }

    // Cambio de tamaño de texto
    if (defined("REQ_SIZE_ID") && REQ_SIZE_ID >= 0 && REQ_SIZE_ID < count($TEXT_SIZES)) {
        $_SESSION["TEXT_SIZE_ID"] = REQ_SIZE_ID;
    }

    $COLOR_ID = $_SESSION["COLOR_ID"];
    $TEXT_SIZE_ID = $_SESSION["TEXT_SIZE_ID"];

?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title><?= $TITLE ?></title>

        <!-- para decirle al navegador que tengo un favicon que no es .ico -->
        <link rel="icon" href="favicon.webp" type="image/webp" sizes="50x50">

        <!-- para decirle al navegador que tengo RSS -->
        <link rel="alternate" type="application/rss+xml" href="rss.xml" title="RSS de record.rat.la">

        <!-- Avisamos al navegador de que se prepare para hacer una petición a los siguientes dominios -->
        <link rel="preconnect dns-prefetch" href="https://www.googletagmanager.com">
        <link rel="preconnect dns-prefetch" href="https://www.google-analytics.com">

        <!-- Revisar: https://css-tricks.com/essential-meta-tags-social-media/ -->
        <meta name="author" content="Inoro" /> <!-- This site was made by https://github.com/1noro -->
        <meta name="description" content="<?= $DESCRIPTION ?>" />
        <meta property="og:locale" content="es_ES" />
        <meta property="og:type" content="article" />
        <meta property="og:title" content="<?= $TITLE ?>" />
        <meta property="og:description" content="<?= $DESCRIPTION ?>" />
        <meta property="og:url" content="<?= $FULL_URL ?>" />
        <meta property="og:site_name" content="record.rat.la" />
        <meta property="og:image" content="<?= $URL . '/' . $PAGE_IMG ?>" />
        <meta property="article:author" content="idex.php?page=inoro.html" />
        <!-- hay que habilitarlo -->
        <!-- <meta property="article:published_time" content="2020-09-21T00:04:15+00:00" /> -->
        <!-- <meta property="article:modified_time" content="2020-09-21T07:23:04+00:00" /> -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:image:src" content="<?= $URL . '/' . $PAGE_IMG ?>" />
        <!-- <meta name="twitter:creator" content="@example" /> -->
        <!-- <meta name="twitter:site" content="cuenta_del_sitio" /> -->
        <meta name="robots" content="index, follow" />
        <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <!-- hay que hacer que los parametros del color y de los tamaños no se agreguen a esta url -->
        <link rel="canonical" href="<?= $FULL_URL ?>" />

        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La carga del script externo se hace después de los estilos para mejorar la performance -->
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'UA-179041248-1', {
                cookie_flags: 'max-age=7200;secure;samesite=none'
            });
        </script>

        <style>
            body {
                background-color: <?= $COLORS[$COLOR_ID]["background"] ?>;
                color: <?= $COLORS[$COLOR_ID]["text"] ?>;
                font-size: <?= $TEXT_SIZES[$TEXT_SIZE_ID]["text"] ?>; /* 1.35em, 14pt */
                /* font-family: Times, Serif; */
                font-family: Helvetica, sans-serif;
            }

            /* --- Enlaces --- */
            a.text_size_link {text-decoration: none;}
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?= $COLORS[$COLOR_ID]["link"] ?>;}
            a:visited {color: <?= $COLORS[$COLOR_ID]["link_visited"] ?>;}
            a:active {color: <?= $COLORS[$COLOR_ID]["link_active"] ?>;}

            /* --- Contenedores HEADER y FOOTER --- */
            header, footer, p.center {text-align: center;}

            header div#web_title {
                font-size: 1.9em; /* este valor multiplica al valor definido en el body */
                font-weight: bold;
                margin: 16px 0px; /* porque es un <div> y no un <p> */
            }

            /* este valor multiplica al valor definido en el body */
            header p#web_nav {font-size: 1.4em;} 

            /* --- contenedor MAIN --- */
            main {
                max-width: 750px;
                /* text-align: justify;
                text-justify: inter-word; */
                margin: 0 auto;
            }

            h1, h2, h3, h4, h5, h6 {
                color: <?= $COLORS[$COLOR_ID]["title"] ?>;
                text-align: left;
            }

            pre {
                padding: 10px;
                overflow: auto;
            }

            code {padding: 1px;}

            pre, code {
                background-color: <?= $COLORS[$COLOR_ID]["code_background"] ?>;
                color: <?= $COLORS[$COLOR_ID]["code_text"] ?>;
            }

            pre, code, samp {font-size: <?= $TEXT_SIZES[$TEXT_SIZE_ID]["code"] ?>; /* 1.1em */}

            img {width: 100%;} /* todas las imágenes menos la del header */

            img.half {
                width: 50%;
                display: block;
                margin: 0 auto;
            }
        </style>

        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La sitúo aquí para mejorar la carga de la web -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-179041248-1"></script>
    </head>

    <body>
        
        <header id="header" aria-label="Cabecera" tabindex="-1">
            <!-- Barra de accesibilidad -->
            <nav aria-label="Enlaces de control de la web" style="text-align: left;">
                <a class="text_size_link" style="font-size: 1.05em;" href="index.php?size=0<?= add_page_if_exists() ?>" aria-label="a, texto a tamaño por defecto.">a</a> 
                <a class="text_size_link" style="font-size: 1.20em;" href="index.php?size=1<?= add_page_if_exists() ?>" aria-label="a, texto a tamaño grande.">a</a> 
                <a class="text_size_link" style="font-size: 1.35em;" href="index.php?size=2<?= add_page_if_exists() ?>" aria-label="a, texto a tamaño enorme.">a</a> / 
                <a href="index.php?page=color.html" aria-label="Cambia la paleta de colores para leer mejor o para molar más.">color</a> / 
                <a href="#main">ir al artículo</a> / 
                <a href="#footer">ir al pié</a>
            </nav>
            <!-- Título del HEADER -->
            <div id="web_title">record <a href="https://youtu.be/dQw4w9WgXcQ" style="text-decoration: none;" aria-label="el enlace perfecto para 🐭.">🐭</a> la</div>
            <!-- Barra de navegación principal -->
            <nav aria-label="Enlaces a las secciones de la página">
                <p id="web_nav">
                    <a href="index.php" aria-label="Páginas recientes.">reciente</a> / 
                    <a href="index.php?page=archive" aria-label="El archivo de páginas ordenadas por fecha.">archivo</a> / 
                    <a href="index.php?page=faq.html" aria-label="Preguntas frecuentes sobre esta página (faq).">faq</a> / 
                    <a href="rss.xml" aria-label="Feed RSS para estar al tanto de las novedades de esta web.">rss</a>
                </p>
            </nav>
            <!-- Alerta sobre las cookies -->
            <p>
                <small>
                    <!-- Debería dar la opción a desactivar la cookies de google -->
                    Esta página guarda una <a href="index.php?page=cookie.html" aria-label="¡Infórmate sobre las cookies!">cookie</a> funcional para el estilo y <strong>ocho</strong> analíticas para google
                </small>
            </p>
        </header>

        <main id="main" aria-label="Contenido principal" tabindex="-1">
<?php
    // Imprimimos lo indicado por la variable $ACTION en el <main>
    switch ($ACTION) {
        default:
        case 0:
            print_reciente();
            break;
        case 1:
            print_archive();
            break;
        case 2:
            $page = COLOR_PAGE;
            print_page(get_page_content($page), get_page_info($page));
            break;
        case 3:
            $page = REQ_PAGE;
            print_page(get_page_content($page), get_page_info($page));
            break;
        case 404:
            $page = E404_PAGE;
            print_page(get_page_content($page), get_page_info($page));
            break;
    }
?>
        </main>

        <footer id="footer" aria-label="Licencias y contactos" tabindex="-1">
            <nav aria-label="Enlace al archivo">
                <p class="center">
                    <a href="index.php?page=archive">[ver más]</a>
                </p>
            </nav>
            <nav aria-label="Moverse por esta página">
                <p>
                    <a href="#header">ir arriba</a> / <a href="#main">ir al artículo</a>
                </p>
            </nav>
            <nav id="contacto" aria-label="Enlaces de contacto">
                <p>
                    <a href="https://github.com/1noro">github</a> / 
                    <a href="https://gitlab.com/1noro">gitlab</a> / 
                    <a href="mailto:ppuubblliicc@protonmail.com">mail</a> (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" aria-label="¡Mándame un correo cifrado con gpg!">gpg</a>)
                </p>
            </nav>
            <nav aria-label="Donaciones">
                <a href="index.php?page=donaciones.html">donaciones - págame un café</a>
            </nav>
            <p>
                <small>
                    Creado por <a href="https://github.com/1noro/record.rat.la">Inoro</a> bajo la licencia <a href="LICENSE" aria-label="Todo el código que sustenta la web está bajo la licencia GPLv3.">GPLv3</a>
                </small>
            </p>
            <p>
                <a rel="license" href="https://creativecommons.org/licenses/by-nc-sa/4.0/" aria-label="Todo el contenido multimedia está bajo la licencia CC-BY-NC-SA.">
                    <img alt="Licencia Creative Commons BY-NC-SA" style="border-width: 0; width: auto;" src="img/cc.png" width="80" height="15"/>
                </a>
            </p>
        </footer>
    </body>
</html>
