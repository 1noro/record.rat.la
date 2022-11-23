<!--
               _     _
     _ __ __ _| |_  | | __ _
    | '__/ _` | __| | |/ _` |
    | | | (_| | |_ _| | (_| |
    |_|  \__,_|\__(_)_|\__,_|

    record.rat.la by Inoro <https://github.com/1noro>

    "Y el pobre anciano Masson se hundió en la negrura de la muerte, con los 
    locos chillidos de las ratas taladrándole los oídos" - Henry Kuttner

-->
<?php

// --- Gestión de cookies ---

/**
 * Opciones por defecto para el almacenamiento de cookies
 * (86400 segundos = 1 día)
 */
// define("COOKIE_OPTIONS", [
//     "expires" => time() + (86400 * 30),
//     "path" => "/",
//     "domain" => $_SERVER['SERVER_NAME'],
//     "secure" => false,
//     "httponly" => true,
//     "samesite" => "Strict"
// ]);

$COLOR_ID = 0;

/**
 * Si se entra por primera vez a la web se guarda un cookie de TEXT_SIZE_ID
 * con el valor por defecto
 */
// $TEXT_SIZE_ID = 2;
// if (isset($_COOKIE["TEXT_SIZE_ID"])) {
//     $TEXT_SIZE_ID = intval($_COOKIE["TEXT_SIZE_ID"]);
// } else {
//     setcookie("TEXT_SIZE_ID", strval($TEXT_SIZE_ID), COOKIE_OPTIONS);
// }

// --- Constantes ---
define("E404_PAGE", "404.html");
define("PAGES_TO_SHOW", 2); // número de páginas a mostrar en la portada, "reciente"
define("POST_FOLDER", "pages/posts/"); // carpeta donde se guardan las páginas
define("COMMON_FOLDER", "pages/common/"); // carpeta donde se guardan las páginas
define("POST_FILENAMES", get_filenames(POST_FOLDER)); // obtenemos todas las páginas de la carpeta POST_FOLDER
define("PAGE_DATETIME_FORMAT", "Y/m/d \· H:i"); // formato de fecha a mostrar una página (https://www.php.net/manual/es/function.date.php)

define("DEF_TITLE_SUFFIX", " - record.rat.la"); // sufijo por defecto del título de la página
define("DEF_TITLE", "Publicaciones recientes"); // título por defecto de la página
define("DEF_DESCRIPTION", "Bienvenido a record.rat.la, donde un servidor, junto a las ratas del cementerio de Salem, registran sus desvaríos mentales. "); // descripción por defecto de la página
define("DEF_PAGE_IMG", "img/article_default_img_white.jpg"); // imagen por defecto del artículo
define("DEF_AUTHOR_USERNAME", "anon"); // datos de autor por defecto

// autor por defecto: Anon
define("AUTHORS", [
    DEF_AUTHOR_USERNAME => ["Anon", "anon.html"],
    "inoro" => ["Inoro", "inoro.html"]
]);

// $TEXT_SIZES = ["1.05em", "1.2em", "1.35em"];

$COLORS = [
    // Melocotón 2, electric boogaloo
    [
        "background" => "#f6f5e3",
        "text" => "#1D1313",
        "title" => "#1D1313",
        "link" => "#0000EE",
        "link_visited" => "#551A8B",
        "link_active" => "#EE0000",
        "code_background" => "#F8F8F8",
        "code_text" => "#000000"
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

/**
 * reduce_h1, en base a un texto html dado reduce el valor de todos los
 * tag <hN> en uno excepto el <h6>. El inconveniente es que los <h5> y
 * los <h6> quedarán al mismo nivel
 */
function reduce_h1(string $html) : string {
    $search = ["<h5", "</h5", "<h4", "</h4", "<h3", "</h3", "<h2", "</h2", "<h1", "</h1"];
    $replace = ["<h6", "</h6", "<h5", "</h5", "<h4", "</h4", "<h3", "</h3", "<h2", "</h2"];
    $html = str_ireplace($search, $replace, $html);
    return $html;
}

/**
 * convert_title_to_link
 */
function convert_title_to_link(string $filename, string $title, string $html) : string {
    $search = "/<h1>(.*)<\/h1>/i";
    $substitution = "<h1><a class=\"title_link\" href=\"show?filename=${filename}\" aria-label=\"Enlace al contenido, ${title}, para verlo individualmente.\">$1</a></h1>";
    return preg_replace($search, $substitution, $html);
}

// --- Obtención de datos de las páginas ---

/**
 * get_filenames, obtiene los nombres de las páginas en la carpeta
 * especificada
 * 
 * @return array<string>
 */
function get_filenames(string $directory) : array {
    $filenames = [];
    $directoryObj = opendir($directory) ?: null;
    while($filename = readdir($directoryObj)) {
        if(($filename != ".") && ($filename != "..")) {
            // push value to array
            $filenames[] = $filename;
        }
    }
    return $filenames;
}

/**
 * get_publication_datetime, obtiene la fecha de un artículo en base al 
 * comentario "publication_date"
 * 
 * @return DateTime
 */
function get_publication_datetime(string $content) : DateTime {
    $regex = '/<!-- publication_datetime (\d{4})(\d{2})(\d{2})T(\d{2})(\d{2}) -->/';
    $matches_count = preg_match_all($regex, $content, $matches, PREG_PATTERN_ORDER);

    // default date: 2000/01/01 00:00
    $year = '2000';
    $month = '01';
    $day = '01';
    $hour = '00';
    $minute = '00';

    if ($matches_count != 0) {
        $year = $matches[1][0];
        $month = $matches[2][0];
        $day = $matches[3][0];
        $hour = $matches[4][0];
        $minute = $matches[5][0];
    }

    $datetime_str = $year."/".$month."/".$day." ".$hour.":".$minute;
    $datetime_obj = date_create($datetime_str, new DateTimeZone("Europe/Madrid"));

    // si la fecha no es válida, se devuelve una válida
    if ($datetime_obj == null) {
        $datetime_obj = new DateTime();
    }

    return $datetime_obj;
}

/**
 * get_author_data, obtiene los datos del autor en base a su
 * alias en el comentario de la primera línea del artículo
 * 
 * @return array{0: string, 1: string, 2: string}
 */
function get_author_data(string $content) : array {
    $regex = '/<!-- author (.*) -->/';
    $matches_count = preg_match_all($regex, $content, $matches, PREG_PATTERN_ORDER);

    $username = DEF_AUTHOR_USERNAME;

    if ($matches_count != 0 && isset(AUTHORS[$matches[1][0]])) {
        $username = $matches[1][0];
    }

    return [AUTHORS[$username][0], AUTHORS[$username][1], $username];
}

/**
 * get_title, obtiene el título del post en base a su contenido
 */
function get_title(string $content) : string {
    preg_match_all("/<h1>(.*)<\/h1>/i", $content, $matches, PREG_PATTERN_ORDER);
    /**
     * quitamos las tags HTML, los espacios sobrantes y luego cambiamos los 
     * caracteres especiales por sus códigos HTML (incluidas las " y ')
     */
    return htmlentities(trim(strip_tags($matches[1][0])), ENT_QUOTES); 
}

/**
 * get_description, obtiene el contenido del primer párrafo <p></p> del
 * artículo y lo coloca como description del mismo
 * 
 * @todo: mejorar la eficiencia de esta función
 * 
 */
function get_description(string $content) : string {
    $defaultText = "Default description";

    $start = strpos($content, '<p>') ?: 0;
    $end = strpos($content, '</p>', $start);
    $paragraph = strip_tags(substr($content, $start, $end - $start + 4));
    $paragraph = str_replace("\n", "", $paragraph);
    // quitamos el exceso de espacios en blanco delante, atrás y en el medio
    $paragraph = preg_replace('/\s+/', ' ', trim($paragraph));

    if ($paragraph == null) {
        $paragraph = $defaultText;
    }
    
    // si la descripción es mayor a 160 caracteres es malo para el SEO
    // con el preg replace eliminamos la ultima palabra par que no quede cortado
    if (strlen($paragraph) > 160) {
        $paragraph = preg_replace('/\W\w+\s*(\W*)$/', '$1', mb_substr($paragraph, 0, 160 - 3)) . "...";
    }

    return $paragraph;
}

/**
 * get_page_content
 */
function get_page_content(string $filepath) : string {
    return file_get_contents($filepath) ?: "Empty page";
}

/**
 * get_page_info, obtiene en formato diccionario el nombre del archivo,
 * fecha, autor y título de un artículo
 * 
 * @return array{
 *  filename: string,
 *  filepath: string,
 *  author_real_name: string,
 *  author_page: string,
 *  author_username: string,
 *  title: string,
 *  description: string,
 *  publication_datetime: DateTime
 * }
 */
function get_page_info(string $filepath) : array {
    $content = get_page_content($filepath);
    $authorData = get_author_data($content);
    return [
        "filename" => basename($filepath),
        "filepath" => $filepath,
        "author_real_name" => $authorData[0],
        "author_page" => $authorData[1],
        "author_username" => $authorData[2],
        "title" => get_title($content),
        "description" => get_description($content),
        "publication_datetime" => get_publication_datetime($content)
    ];
}

/**
 * get_img, obtiene la primera imagen mostrada en el artículo
 * 
 * @todo optimizar (sacar de lo que se carga en el main)
 */
function get_img(string $filepath) : string {
    $html = file_get_contents($filepath) ?: "";
    preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $html, $matches);
    if (isset($matches["src"])) {
        return $matches["src"];
    }
    return DEF_PAGE_IMG;
}

/**
 * get_sorted_page_info
 * 
 * @return array<
 *  int,
 *  array{
 *      filename: string,
 *      filepath: string,                   
 *       author_real_name: string,
 *       author_page: string,
 *       author_username: string,
 *       title: string,
 *       description: string,
 *       publication_datetime: DateTime
 *  }>
 */
function get_sorted_page_info() : array {
    // creamos $fileInfoArr y $datetimeArr previamente para ordenar 
    // los archivos por fecha
    $fileInfoArr = [];
    $datetimeArr = [];
    foreach(POST_FILENAMES as $filename) {
        $fileInfo = get_page_info(POST_FOLDER . $filename);
        array_push($fileInfoArr, $fileInfo);
        array_push($datetimeArr, $fileInfo["publication_datetime"]);
    }

    // en base a los dos arrays anteriores ordeno por fecha
    array_multisort($datetimeArr, SORT_DESC, $fileInfoArr);

    return $fileInfoArr;
}

// --- Impresión de contenidos ---

/**
 * home_action, imprime la portada (las N páginas más recientes)
 */
function home_action() : void {
    $pageInfoArr = get_sorted_page_info();

    echo "<h1>Publicaciones recientes</h1>\n";
    echo "
        <p>
            Bienvenido a <em>record.rat.la</em>, donde 
            <a href=\"author?username=inoro\" aria-label=\"Página del autor Inoro.\">un servidor</a>, 
            junto a las ratas del cementerio de Salem, registran sus desvaríos 
            mentales. Estas son las publicaciones más recientes, si quieres 
            leer más puedes ir al <a href=\"archive\">archivo</a>. Y si estás 
            confuso y no entiendes de que vá todo esto puedes leer las 
            <a href=\"faq\">preguntas frecuentes</a>.
        </p>\n
    ";

    $number = 1;
    foreach($pageInfoArr as $pageInfo) {
        echo "<article>\n";
        $content = convert_title_to_link(
            $pageInfo["filename"],
            $pageInfo["title"],
            get_page_content($pageInfo["filepath"])
        );
        $content = reduce_h1($content);
        print_page($content, $pageInfo);
        echo "</article>\n";
        if ($number >= PAGES_TO_SHOW) {
            break;
        }
        $number++;
    }
}

/**
 * archive_action, imprime la página 'archivo', donde se listan las
 * páginas ordenadas por fecha DESC
 */
function archive_action() : void {
    $currentYear = "";
    $currentMonth = "";

    $pageInfoArr = get_sorted_page_info();

    echo "<h1>Historias de una rata</h1>\n";
    echo "<p>Registro cronológico de todas las publicaciones de la web.</p>\n";

    foreach($pageInfoArr as $pageInfo) {
        $year = date_format($pageInfo["publication_datetime"], "Y");
        $month = date_format($pageInfo["publication_datetime"], "n"); // n: 1..12 / m: 01..12
        $dayHourStr = date_format($pageInfo["publication_datetime"], "d \· H:i");

        if ($currentYear != $year) {
            $currentYear = $year;
            printf("<h2>– Año %s –</h2>\n", $year);
        }

        if ($currentMonth != $month) {
            $currentMonth = $month;
            printf("<h3>%s</h3>\n", MONTHS[intval($month) - 1]);
        }

        printf(
            '<blockquote>%s · <a href="show?filename=%s">%s</a><br>%s</blockquote>' . "\n",
            $dayHourStr,
            $pageInfo["filename"],
            $pageInfo["title"],
            $pageInfo["description"]
        );
    }

    printf("<p>Hay un total de %d páginas en la web.</p>\n", count(POST_FILENAMES));
}

/**
 * print_page, imprime la página de un artículo cuyo nombre de archivo
 * se pasa como parámetro
 * 
 * @param array{
 *  filename: string,
 *  filepath: string,
 *  author_real_name: string,
 *  author_page: string,
 *  author_username: string,
 *  title: string,
 *  description: string,
 *  publication_datetime: DateTime
 * } $pageInfo
 */
function print_page(string $pageContent, array $pageInfo) : void {
    echo $pageContent . "\n";
    printf(
        '<p style="text-align:right;"><small>Publicado por <a href="author?username=%s" aria-label="Página del autor %s.">%s</a> el %s</small></p>' . "\n",
        $pageInfo["author_username"],
        $pageInfo["author_real_name"],
        $pageInfo["author_real_name"],
        date_format($pageInfo["publication_datetime"], PAGE_DATETIME_FORMAT)
    );
}

// --- Variables globales ---
$TITLE = DEF_TITLE;
$DESCRIPTION = DEF_DESCRIPTION; 
$PAGE_IMG = DEF_PAGE_IMG;
$ACTION = 0;
$FILEPATH = "";
$OG_TYPE = "website";
$ARTICLE_AUTHOR_USERNAME = "inoro";
$ARTICLE_PUBLISHED_DATETIME = "";

// --- Montamos las variables URL, FULL_URL y CANONICAL_URL
$URL = "http";
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on') {
    $URL = "https";
}
$URL .= "://";
$URL .= $_SERVER["HTTP_HOST"];
$FULL_URL = $URL . $_SERVER["REQUEST_URI"];
$CANONICAL_URL = $FULL_URL;

// --- Lógica de impresión ---

// route the request internally
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ('/' === $uri) {
    // Home
    $ACTION = 0;
    $TITLE = DEF_TITLE;
    $DESCRIPTION = DEF_DESCRIPTION;
} elseif ('/archive' === $uri) {
    // Archivo
    $ACTION = 1;
    $TITLE = "Historias de una rata";
    $DESCRIPTION = "Registro cronológico de todas las publicaciones de la web.";
} elseif ('/show' === $uri && isset($_GET['filename'])) {
    if (in_array($_GET['filename'], POST_FILENAMES)) {
        // Post
        $ACTION = 2;
        $filename = $_GET['filename'];
        $FILEPATH = POST_FOLDER . $filename;
        $fileInfo = get_page_info($FILEPATH);
        $TITLE = $fileInfo["title"];
        $OG_TYPE = "article";
        $ARTICLE_AUTHOR_USERNAME = $fileInfo["author_username"];
        $ARTICLE_PUBLISHED_DATETIME = date_format($fileInfo["publication_datetime"], DATE_W3C);
        $DESCRIPTION = $fileInfo["description"];
        $PAGE_IMG = get_img($FILEPATH);
    } else {
        // Error 404 (Post not found)
        $ACTION = 404;
    }
} elseif ('/author' === $uri && isset($_GET['username'])) {
    if (isset(AUTHORS[$_GET['username']])) {
        // Author page
        $ACTION = 2;
        $filename = AUTHORS[$_GET['username']][1];
        $FILEPATH = COMMON_FOLDER . $filename;
        $fileInfo = get_page_info($FILEPATH);
        $TITLE = $fileInfo["title"];
        $OG_TYPE = "profile";
        // @todo: agregar variables del og:type profile
        $DESCRIPTION = $fileInfo["description"];
        $PAGE_IMG = get_img($FILEPATH);
    } else {
        // Error 404 (Username not found)
        $ACTION = 404;
    }
} elseif ('/faq' === $uri) {
    // FAQ
    $ACTION = 2;
    $filename = "faq.html";
    $FILEPATH = COMMON_FOLDER . $filename;
    $fileInfo = get_page_info($FILEPATH);
    $TITLE = $fileInfo["title"];
    $OG_TYPE = "article";
    $ARTICLE_AUTHOR_USERNAME = $fileInfo["author_username"];
    $ARTICLE_PUBLISHED_DATETIME = date_format($fileInfo["publication_datetime"], DATE_W3C);
    $DESCRIPTION = $fileInfo["description"];
    $PAGE_IMG = get_img($FILEPATH);
} elseif ('/donations' === $uri) {
    // Donations
    $ACTION = 2;
    $filename = "donaciones.html";
    $FILEPATH = COMMON_FOLDER . $filename;
    $fileInfo = get_page_info($FILEPATH);
    $TITLE = $fileInfo["title"];
    $OG_TYPE = "article";
    $ARTICLE_AUTHOR_USERNAME = $fileInfo["author_username"];
    $ARTICLE_PUBLISHED_DATETIME = date_format($fileInfo["publication_datetime"], DATE_W3C);
    $DESCRIPTION = $fileInfo["description"];
    $PAGE_IMG = get_img($FILEPATH);
} elseif ('/description' === $uri) {
    // Description
    $ACTION = 2;
    $filename = "descripcion.html";
    $FILEPATH = COMMON_FOLDER . $filename;
    $fileInfo = get_page_info($FILEPATH);
    $TITLE = $fileInfo["title"];
    $OG_TYPE = "article";
    $ARTICLE_AUTHOR_USERNAME = $fileInfo["author_username"];
    $ARTICLE_PUBLISHED_DATETIME = date_format($fileInfo["publication_datetime"], DATE_W3C);
    $DESCRIPTION = $fileInfo["description"];
    $PAGE_IMG = get_img($FILEPATH);
} elseif ('/cookie' === $uri) {
    // Cookie
    $ACTION = 2;
    $filename = "cookie.html";
    $FILEPATH = COMMON_FOLDER . $filename;
    $fileInfo = get_page_info($FILEPATH);
    $TITLE = $fileInfo["title"];
    $OG_TYPE = "article";
    $ARTICLE_AUTHOR_USERNAME = $fileInfo["author_username"];
    $ARTICLE_PUBLISHED_DATETIME = date_format($fileInfo["publication_datetime"], DATE_W3C);
    $DESCRIPTION = $fileInfo["description"];
    $PAGE_IMG = get_img($FILEPATH);
} else {
    // Error 404
    $ACTION = 404;
}

if ($ACTION == 404) {
    $ACTION = 2; // esto es muy poco elegante
    $FILEPATH = COMMON_FOLDER . E404_PAGE;
    $fileInfo = get_page_info($FILEPATH);
    $TITLE = $fileInfo["title"];
    http_response_code(404);
}

?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?= $TITLE . DEF_TITLE_SUFFIX ?></title>

        <!-- ## LINK ## -->
        <!-- Para decirle a google que la URL original es esta, y no la que se está usando -->
        <link rel="canonical" href="<?= $CANONICAL_URL ?>">

        <!-- Para decirle al navegador que tengo RSS -->
        <link rel="alternate" type="application/rss+xml" href="rss.xml" title="RSS de record.rat.la">

        <!-- Avisamos al navegador de que se prepare para hacer una petición a los siguientes dominios -->
        <!-- <link rel="preconnect" href="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ">
        <link rel="dns-prefetch" href="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ"> -->
        <link rel="preload" href="res/eb-garamond/EBGaramond-Regular.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-Italic.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-Bold.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-BoldItalic.ttf" as="font" type="font/ttf" crossorigin>

        <!-- ## META ## -->
        <!-- Revisar: https://css-tricks.com/essential-meta-tags-social-media/ -->
        <meta name="title" content="<?= $TITLE ?>">
        <meta name="description" content="<?= $DESCRIPTION ?>">
        <meta name="author" content="Inoro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="theme-color" content="#000000"> <!-- Para que el navegador sepa que color debe usar en el marco -->

        <!-- OG -->
        <meta property="og:type" content="<?= $OG_TYPE ?>">
<?php if ($OG_TYPE == "article") { ?>
        <meta property="article:author" content="<?= $URL ?>/author?username=<?= $ARTICLE_AUTHOR_USERNAME ?>">
        <meta property="article:published_time" content="<?= $ARTICLE_PUBLISHED_DATETIME ?>">
        <!-- <meta property="article:modified_time" content="2020-09-21T07:23:04+00:00"> -->
<?php } ?>
        <meta property="og:url" content="<?= $CANONICAL_URL ?>">
        <meta property="og:site_name" content="record.rat.la">
        <meta property="og:locale" content="es_ES">
        <meta property="og:title" content="<?= $TITLE ?>">
        <meta property="og:description" content="<?= $DESCRIPTION ?>">
        <meta property="og:image" content="<?= $URL . '/' . $PAGE_IMG ?>">
        <meta property="og:image:alt" content="Portada del artículo.">
        <meta property="og:image:type" content="<?= mime_content_type($PAGE_IMG) ?>">
        <!-- <meta property="og:image:width" content="1200"> -->
        <!-- <meta property="og:image:height" content="1200"> -->

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="<?= $CANONICAL_URL ?>">
        <meta property="twitter:title" content="<?= $TITLE ?>">
        <meta property="twitter:description" content="<?= $DESCRIPTION ?>">
        <meta property="twitter:image" content="<?= $URL . '/' . $PAGE_IMG ?>">
        <!-- <meta name="twitter:image:src" content="<?= $URL . '/' . $PAGE_IMG ?>"> -->
        <!-- <meta name="twitter:creator" content="@example"> -->
        <!-- <meta name="twitter:site" content="cuenta_del_sitio"> -->

        <!-- Scraping -->
        <meta name="robots" content="index, follow">
        <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        
        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La carga del script externo se hace después de los estilos para mejorar la performance -->
        <!-- <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-W3KC9CP7ZQ');
        </script> -->

        <style>
            @font-face {
                font-family: 'eb-garamond';
                src: local('EB Garamond'), local('EBGaramond'),
                    url('res/eb-garamond/EBGaramond-Regular.ttf');
                font-weight: normal;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-Italic.ttf');
                font-weight: normal;
                font-style: italic;
                font-display: swap;
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-Bold.ttf');
                font-weight: bold;
                font-style: normal;
                font-display: swap;
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-BoldItalic.ttf');
                font-weight: bold;
                font-style: italic;
                font-display: swap;
            }

            body {
                background-color: <?= $COLORS[$COLOR_ID]["background"] ?>;
                color: <?= $COLORS[$COLOR_ID]["text"] ?>;
                /* font-family: 'Times New Roman', Times, serif; */
                /* font-family: Helvetica, sans-serif; */
                font-family: 'eb-garamond', serif;
                font-size: 1.30em;
            }

            /* --- Enlaces --- */
            a.text_size_link {text-decoration: none;}
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?= $COLORS[$COLOR_ID]["link"] ?>;}
            a:visited {color: <?= $COLORS[$COLOR_ID]["link_visited"] ?>;}
            a:active {color: <?= $COLORS[$COLOR_ID]["link_active"] ?>;}

            a.title_link:link {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}
            a.title_link:visited {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}
            a.title_link:active {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}

            /* --- Contenedores HEADER y FOOTER --- */
            header, footer {text-align: center;}

            header div#web_title {
                /* este valor multiplica al valor definido en el body */
                font-size: 1.9em;
                font-weight: bold;
                margin: 16px 0px; /* porque es un <div> y no un <p> */
            }

            /* este valor multiplica al valor definido en el body */
            header p#web_nav {font-size: 1.4em;}
            header p#header_quote {
                max-width: 550px;
                margin: 0 auto;
            }

            /* --- contenedor MAIN --- */
            main {
                max-width: 800px;
                margin: 0 auto;
                /* text-align: justify;
                text-justify: inter-word; */
            }

            h1, h2, h3, h4, h5, h6 {color: <?= $COLORS[$COLOR_ID]["title"] ?>;}
            img {width: 100%;} /* todas las imágenes menos la del header */
            img.half {width: 50%; display: block; margin: 0 auto;}
            pre {padding: 10px; overflow: auto;}
            code {padding: 1px;}

            pre, code {
                background-color: <?= $COLORS[$COLOR_ID]["code_background"] ?>;
                color: <?= $COLORS[$COLOR_ID]["code_text"] ?>;
            }
        </style>

        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La sitúo aquí para mejorar la carga de la web -->
        <!-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ"></script> -->
    </head>

    <body>
        
        <header id="header" aria-label="Cabecera" tabindex="-1">
            <!-- Barra de accesibilidad -->
            <nav aria-label="Enlaces de control de la web" style="text-align: left;">
                <a href="#main">ir al artículo</a> / 
                <a href="#footer">ir al pié</a>
            </nav>
            <!-- Título del HEADER -->
            <div id="web_title">record <a href="https://youtu.be/dQw4w9WgXcQ" style="text-decoration: none;" aria-label="el enlace perfecto para 🐭.">🐭</a> la</div>
            <!-- Barra de navegación principal -->
            <nav aria-label="Enlaces a las secciones de la página">
                <p id="web_nav">
                    <a href="/" aria-label="Páginas recientes.">reciente</a> &nbsp;
                    <a href="archive" aria-label="El archivo de páginas ordenadas por fecha.">archivo</a> &nbsp;
                    <a href="faq" aria-label="Preguntas frecuentes sobre esta página (faq).">faq</a> &nbsp;
                    <a href="rss.xml" aria-label="Feed RSS para estar al tanto de las novedades de esta web.">rss</a>
                </p>
            </nav>
            <!-- Cita de Henry Kuttner -->
            <p id="header_quote">
                <small>
                    <em>
                        "Y el pobre anciano Masson se hundió en la negrura de 
                        la muerte, con los locos chillidos de las ratas 
                        taladrándole los oídos"
                    </em> – Henry Kuttner
                </small>
            </p>
            <!-- Alerta sobre las cookies -->
            <!-- Debería dar la opción a desactivar la cookies de google -->
            <!-- <p>
                <small>
                    Esta página guarda dos <a href="cookie" aria-label="¡Infórmate sobre las cookies!">cookies</a> funcionales para el estilo y <strong>tres</strong> analíticas para google
                </small>
            </p> -->
        </header>

        <main id="main" aria-label="Contenido principal" tabindex="-1">
<?php
    // Imprimimos lo indicado por la variable $ACTION en el <main>
    switch ($ACTION) {
        default:
        case 0:
            home_action();
            break;
        case 1:
            archive_action();
            break;
        case 2:
            print_page(get_page_content($FILEPATH), get_page_info($FILEPATH));
            break;
    }
?>
        </main>

        <footer id="footer" aria-label="Licencias, contactos y más enlaces." tabindex="-1">
            <nav aria-label="Enlace al archivo de publicaciones">
                <p>
                    <a href="archive">&laquo;más publicaciones&raquo;</a>
                </p>
            </nav>
            <nav aria-label="Moverse por esta página.">
                <p>
                    <a href="#header">ir arriba</a> / <a href="#main">ir al artículo</a>
                </p>
            </nav>
            <nav id="contacto" aria-label="Enlaces de contacto.">
                <p>
                    <a href="https://github.com/1noro" aria-label="Enlace a mi perfil de GitHub">github</a> / 
                    <a href="https://gitlab.com/1noro" aria-label="Enlace a mi perfil de GitLab">gitlab</a> / 
                    <a href="https://tilde.zone/@1noro" aria-label="Enlace a mi perfil de Mastodon">mastodon</a> / 
                    mail (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" aria-label="¡Mándame un correo cifrado con gpg!">gpg</a>)
                </p>
            </nav>
            <nav aria-label="Puedes contribuir a mis proyectos donando en estos enlaces.">
                <a href="donations">donaciones &middot; págame un café</a>
            </nav>
            <p>
                <small>
                    Software creado por <a href="https://github.com/1noro/record.rat.la">Inoro</a> bajo la licencia <a rel="license" href="LICENSE.GPL-3.0.txt" aria-label="Todo el código que sustenta la web está bajo la licencia GPLv3.">GPLv3</a><br>
                    Multimedia bajo la licencia <a rel="license" href="LICENSE.CC-BY-SA-4.0.txt" aria-label="Texto de la licencia Creative Commons BY-SA-4.0.">Creative Commons BY-SA-4.0</a>
                </small>
            </p>
        </footer>
    </body>
</html>
