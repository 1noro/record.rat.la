<?php

// --- Funciones ---
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

// --- Constantes ---
define("POST_FOLDER", "pages/posts/"); // carpeta donde se guardan las páginas
define("COMMON_FOLDER", "pages/common/"); // carpeta donde se guardan las páginas
define("POST_FILENAMES", get_filenames(POST_FOLDER)); // obtenemos todas las páginas de la carpeta POST_FOLDER

define("DEF_AUTHOR_USER_NAME", "anon"); // datos de autor por defecto

// autor por defecto: Anon
define("AUTHORS", [
    DEF_AUTHOR_USER_NAME => 0,
    "inoro" => 0
]);

// --- Variables globales ---
$ACTION = 0;
$locationURL = "https://rats.land/";
$locationURL_error = "https://rats.land/error/";

// --- Lógica de rutas (Ingress) ---
// route the request internally
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ('/' === $uri) {
    $locationURL = "https://rats.land/";
} elseif ('/archive' === $uri) {
    $locationURL = "https://rats.land/post/";
} elseif ('/show' === $uri && isset($_GET['filename'])) {
    if (in_array($_GET['filename'], POST_FILENAMES)) {
        // Post
        // $path = POST_FOLDER . $_GET['filename'];
        // $page = new ContentPage($path, get_full_uri());
        if ($_GET['filename'] == 'mi-software.html') {
            $locationURL = "https://rats.land/mi-software/";
        } else if ($_GET['filename'] == 'fallen-angels-wong-kar-wai.html') {
            $locationURL = "https://rats.land/post/fallen-angels-wong-kar-wai/";
        } else if ($_GET['filename'] == 'estoy-leyendo-sobre-estoicismo.html') {
            $locationURL = "https://rats.land/post/estoy-leyendo-sobre-estoicismo/";
        } else if ($_GET['filename'] == 'le-sommet-des-dieux.html') {
            $locationURL = "https://rats.land/post/le-sommet-des-dieux/";
        } else if ($_GET['filename'] == 'la-cinta-blanca-michael-haneke.html') {
            $locationURL = "https://rats.land/post/la-cinta-blanca-michael-haneke/";
        } else if ($_GET['filename'] == 'emocionado-link-blog.html') {
            $locationURL = "https://rats.land/post/emocionado-link-blog/";
        } else if ($_GET['filename'] == 'cd-fuzzy-finder.html') {
            $locationURL = "https://rats.land/post/cd-fuzzy-finder/";
        } else if ($_GET['filename'] == 'expandir-ext4-dinamicamente.html') {
            $locationURL = "https://rats.land/post/expandir-ext4-dinamicamente/";
        } else if ($_GET['filename'] == 'haxan-brujeria-a-traves-del-tiempo.html') {
            $locationURL = "https://rats.land/post/haxan-brujeria-a-traves-del-tiempo/";
        } else if ($_GET['filename'] == 'yojimbo-japon-inspira-western.html') {
            $locationURL = "https://rats.land/post/yojimbo-japon-inspira-western/";
        } else if ($_GET['filename'] == 'aranas-antiguas-sueno-esoterico.html') {
            $locationURL = "https://rats.land/post/aranas-antiguas-sueno-esoterico/";
        } else if ($_GET['filename'] == 'comandos-utiles-git.html') {
            $locationURL = "https://rats.land/post/comandos-utiles-git/";
        } else if ($_GET['filename'] == 'drivers-nvidia-arch-linux.html') {
            $locationURL = "https://rats.land/post/drivers-nvidia-arch-linux/";
        } else if ($_GET['filename'] == 'gitlab-nginx.html') {
            $locationURL = "https://rats.land/post/gitlab-nginx/";
        } else if ($_GET['filename'] == 'solucion-series-numericas-java-python.html') {
            $locationURL = "https://rats.land/post/series-numericas-en-java-y-python/";
        } else if ($_GET['filename'] == 'la-forma-mas-rapida-de-hacer-un-git-push.html') {
            $locationURL = "https://rats.land/post/la-forma-mas-rapida-de-hacer-un-git-push/";
        } else if ($_GET['filename'] == 'cowboy-bebop.html') {
            $locationURL = "https://rats.land/post/cowboy-bebop/";
        } else if ($_GET['filename'] == 'habilitar-http2-nginx.html') {
            $locationURL = "https://rats.land/post/habilitar-http2-nginx/";
        } else if ($_GET['filename'] == 'configurando-nginx-para-esta-web.html') {
            $locationURL = "https://rats.land/post/un-servidor-para-record-rat-la/";
        } else if ($_GET['filename'] == 'los-buenos-dias-en-los-chats.html') {
            $locationURL = "https://rats.land/post/los-buenos-dias-en-los-chats-on-line/";
        } else if ($_GET['filename'] == 'ubiquiti-er-movistartv.html') {
            $locationURL = "https://github.com/1noro/ubiquiti-er-movistartv";
        } else if ($_GET['filename'] == 'genesis.html') {
            $locationURL = "https://rats.land/post/genesis/";
        } else {
            $locationURL = 'https://rats.land/post/';
        }
    } else {
        // Error 404 (Post not found)
        $locationURL = $locationURL_error;
    }
} elseif ('/author' === $uri && isset($_GET['username'])) {
    if (isset(AUTHORS[$_GET['username']])) {
        // Author page
        // $path = COMMON_FOLDER . get_author_by_user_name($_GET['username'])->page_file_name;
        // $page = new ContentPage($path, get_full_uri());
        if ($_GET['username'] == 'inoro') {
            $locationURL = "https://rats.land/inoro/";
        } else {
            $locationURL = "https://rats.land/";
        }
    } else {
        // Error 404 (Username not found)
        $locationURL = $locationURL_error;
    }
} elseif ('/faq' === $uri) {
    $locationURL = "https://rats.land/faq/";
} elseif ('/donations' === $uri) {
    $locationURL = "https://rats.land/donaciones/";
} elseif ('/description' === $uri) {
    $locationURL = "https://rats.land/info/";
} elseif ('/cookie' === $uri) {
    $locationURL = "https://rats.land/";
} else {
    // Error 404 (Page not found)
    $locationURL = $locationURL_error;
}

// Lógica de errores
if ($ACTION == 404) {
    $page = new ContentPage(COMMON_FOLDER . E404_PAGE, get_full_uri());
    http_response_code(404);
}

echo '<h1>record.rat.la se muda a <a href="https://rats.land/">rats.land</a></h1>';
echo 'Redirigiendo a <a href="' . $locationURL . '">' . $locationURL . '</a>...';

// header("HTTP/1.1 301 Moved Permanently");

// http_response_code(301);
// header("Location: $locationURL");
exit();
