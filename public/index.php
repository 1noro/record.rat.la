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
define("DEF_PAGE_IMG", "img/article_default_img_peach.jpg"); // imagen por defecto del artículo
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
                /* font-display: swap; */
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-Italic.ttf');
                font-weight: normal;
                font-style: italic;
                /* font-display: swap; */
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-Bold.ttf');
                font-weight: bold;
                font-style: normal;
                /* font-display: swap; */
            }

            @font-face {
                font-family: 'eb-garamond';
                src: url('res/eb-garamond/EBGaramond-BoldItalic.ttf');
                font-weight: bold;
                font-style: italic;
                /* font-display: swap; */
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
            /* Es importante mantener el orden: link - visited - hover - active */
            a:link {color: <?= $COLORS[$COLOR_ID]["link"] ?>;}
            a:visited {color: <?= $COLORS[$COLOR_ID]["link_visited"] ?>;}
            a:active {color: <?= $COLORS[$COLOR_ID]["link_active"] ?>;}

            a.title_link:link {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}
            a.title_link:visited {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}
            a.title_link:active {color: <?= $COLORS[$COLOR_ID]["text"] ?>;}

            /* --- Contenedores HEADER y FOOTER --- */
            header, footer {text-align: center;}

            svg#title_rat {
                width: initial;
                height: 0.8em;
            }

            svg#title_rat path {
                fill: <?= $COLORS[$COLOR_ID]["text"] ?>;
            }

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
            <div id="web_title">
                <!-- record <a href="https://youtu.be/dQw4w9WgXcQ" style="text-decoration: none;" aria-label="El enlace perfecto para 🐭">🐭</a> la -->
                record
                <a href="https://youtu.be/dQw4w9WgXcQ" style="text-decoration: none;" aria-label="El enlace perfecto">
                    <svg version="1.1" id="title_rat" width="50" height="50" viewBox="0 0 50 49.999999" xmlns="http://www.w3.org/2000/svg">
                        <defs id="defs6" />
                        <g id="g8" transform="translate(-733.3356,-721.2295)">
                            <path style="stroke-width:0.265138" d="m 751.28523,771.05588 c -1.9803,-0.33288 -4.84505,-1.28442 -6.35035,-2.1093 -4.75001,-2.60292 -7.01888,-5.49494 -9.07533,-11.56787 -0.71216,-2.10312 -0.84558,-2.93052 -0.95356,-5.91364 -0.18859,-5.21016 0.76832,-9.08754 3.26664,-13.2364 0.50391,-0.83682 0.91619,-1.67922 0.91619,-1.872 0,-0.19277 -0.35794,-1.41886 -0.79542,-2.72465 -1.39451,-4.16236 -1.01531,-7.56374 1.11277,-9.98137 2.22481,-2.52752 5.11202,-3.10948 7.88091,-1.5885 2.18179,1.19847 3.16406,2.73737 3.86172,6.05007 0.11336,0.53828 0.27515,0.70853 0.56693,0.59656 0.22455,-0.0862 1.15989,-0.15667 2.07853,-0.15667 h 1.67024 l 0.29063,-1.15421 c 0.42513,-1.68833 1.83577,-3.61833 3.30595,-4.52313 3.4376,-2.11559 7.52265,-0.59918 9.48283,3.52014 0.87065,1.82965 0.7865,4.55106 -0.23517,7.60561 l -0.78835,2.35698 1.04111,2.36586 c 1.86894,4.24702 2.63716,7.47347 2.80223,11.76902 0.0798,2.07803 0.18278,3.77824 0.22873,3.77824 0.046,0 0.38205,-0.13602 0.74691,-0.30226 0.888,-0.4046 2.13513,0.11098 2.7383,1.13204 0.53146,0.8997 1.07301,0.95869 2.01302,0.21928 1.44862,-1.13949 1.19691,-3.47137 -0.61006,-5.65159 -1.29642,-1.56421 -1.3813,-2.57119 -0.37292,-4.42393 1.08925,-2.00131 3.13045,-3.43509 4.89037,-3.43509 0.93244,0 1.04563,0.647 0.28367,1.62136 -0.65743,0.84072 -1.89176,3.30231 -1.89176,3.77271 0,0.20574 0.19785,0.53781 0.43969,0.73796 0.24183,0.20013 0.77864,1.07976 1.19292,1.95472 1.80299,3.80799 0.32849,7.83135 -3.44251,9.39335 -0.6874,0.28473 -0.78739,0.47879 -0.92283,1.79102 -0.43076,4.17366 -4.18403,7.61838 -7.32064,6.71881 -1.03265,-0.29616 -2.49591,-1.34152 -2.67332,-1.61874 -0.3441,-0.53769 -0.60766,-0.20498 -2.18058,1.06773 -3.7124,3.00384 -8.96499,4.51938 -13.19749,3.80789 z m 19.87145,-7.10045 c 1.61354,-0.83439 2.46407,-4.29246 1.31216,-5.33493 -0.3068,-0.27765 -0.7644,-0.90749 -1.0169,-1.39967 l -0.45909,-0.89485 -0.34973,1.29229 c -0.19235,0.71076 -0.69324,2.06329 -1.11309,3.00564 -0.65983,1.48098 -0.72148,1.81447 -0.45458,2.45882 0.25268,0.61003 0.85384,1.21525 1.24668,1.25512 0.0437,0.005 0.41925,-0.16765 0.83455,-0.38242 z m -11.71922,-15.51229 c 1.03045,-0.96 1.13586,-4.52401 0.15503,-5.24122 -0.67802,-0.49578 -1.76909,-0.40203 -2.4242,0.20832 -0.4958,0.4619 -0.58028,0.82578 -0.58028,2.49957 0,1.80051 0.0565,2.00342 0.69861,2.5085 0.87445,0.68784 1.43227,0.69428 2.15084,0.0249 z m -10.52585,-0.49279 c 0.59266,-0.4799 1.37337,-3.03938 0.75245,-5.09277 -0.35672,-1.17967 -1.26251,-1.51045 -2.6106,-0.78897 -0.67394,0.36068 -1.07102,1.65979 -1.07102,3.50403 0,1.3063 0.107,1.67032 0.6508,2.21412 0.77577,0.77577 1.46156,0.82501 2.27837,0.16359 z" id="path170" />
                        </g>
                    </svg>
                </a>
                la
            </div>
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

        <footer id="footer" aria-label="Licencias, contactos y más enlaces" tabindex="-1">
            <nav aria-label="Enlace al archivo de publicaciones">
                <p>
                    <a href="archive">&laquo;más publicaciones&raquo;</a>
                </p>
            </nav>
            <nav aria-label="Moverse por esta página">
                <p>
                    <a href="#header">ir arriba</a> / <a href="#main">ir al artículo</a>
                </p>
            </nav>
            <nav id="contacto" aria-label="Enlaces de contacto">
                <p>
                    <a href="https://github.com/1noro" aria-label="Enlace a mi perfil de GitHub">github</a> / 
                    <a href="https://gitlab.com/1noro" aria-label="Enlace a mi perfil de GitLab">gitlab</a> / 
                    <a href="https://tilde.zone/@1noro" aria-label="Enlace a mi perfil de Mastodon">mastodon</a> / 
                    mail (<a href="res/publickey.ppuubblliicc@protonmail.com.asc" aria-label="¡Mándame un correo cifrado con gpg!">gpg</a>)
                </p>
            </nav>
            <nav aria-label="Puedes contribuir a mis proyectos donando en estos enlaces">
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
