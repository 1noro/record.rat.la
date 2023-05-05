<?php

// --- Constantes ---
define("COMMON_FOLDER", "pages/common/"); // carpeta donde se guardan las páginas
define("POST_FILENAMES", get_filenames(POST_FOLDER)); // obtenemos todas las páginas de la carpeta POST_FOLDER

// autor por defecto: Anon
define("AUTHORS", [
    DEF_AUTHOR_USER_NAME => new Author(DEF_AUTHOR_USER_NAME, "Anon", "anon.html"),
    "inoro" => new Author("inoro", "Inoro", "inoro.html")
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
        } else if ($_GET['filename'] == '') {
            $locationURL = "https://rats.land/";
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
    $locationURL = "https://rats.land/donations/";
} elseif ('/description' === $uri) {
    $locationURL = "https://rats.land/info/";
} elseif ('/cookie' === $uri) {
    $locationURL = $locationURL_error;
} else {
    // Error 404 (Page not found)
    $locationURL = $locationURL_error;
}

// Lógica de errores
if ($ACTION == 404) {
    $page = new ContentPage(COMMON_FOLDER . E404_PAGE, get_full_uri());
    http_response_code(404);
}

// header("HTTP/1.1 301 Moved Permanently");
http_response_code(301);
header("Location: $locationURL");
exit();
