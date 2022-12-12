<?php
/*
               _     _
     _ __ __ _| |_  | | __ _
    | '__/ _` | __| | |/ _` |
    | | | (_| | |_ _| | (_| |
    |_|  \__,_|\__(_)_|\__,_|

    record.rat.la by Inoro <https://github.com/1noro>

    "Y el pobre anciano Masson se hundió en la negrura de la muerte, con los 
    locos chillidos de las ratas taladrándole los oídos" - Henry Kuttner
*/

// --- Gestión de cookies ---

/**
 * Opciones por defecto para el almacenamiento de cookies
 * (86400 segundos = 1 día)
 */
define("COOKIE_OPTIONS", [
    "expires" => time() + (86400 * 30 * 6), // 6 meses (lo recomendado para el consentimiento de las cookies)
    "path" => "/",
    "domain" => $_SERVER['SERVER_NAME'],
    "secure" => true,
    "httponly" => true,
    "samesite" => "none"
]);

/**
 * Comprobamos si existe la cookie COOKIE_COMPLIANCE_ACCEPT y, en caso de 
 * existir, si tiene un valor válido. Después en función de eso redefinimos 
 * la variable COOKIE_COMPLIANCE_ACCEPT con el valor que tiene la cookie, que 
 * definirá si el usuario aceptó o no la política de cookies.
 */
$COOKIE_COMPLIANCE_ACCEPT = -1;
if (isset($_COOKIE["COOKIE_COMPLIANCE_ACCEPT"]) && intval($_COOKIE["COOKIE_COMPLIANCE_ACCEPT"]) == 0) {
    $COOKIE_COMPLIANCE_ACCEPT = 0;
} elseif (isset($_COOKIE["COOKIE_COMPLIANCE_ACCEPT"]) && intval($_COOKIE["COOKIE_COMPLIANCE_ACCEPT"]) == 1) {
    $COOKIE_COMPLIANCE_ACCEPT = 1;
}

/**
 * En el caso de que se haya aceptado o declinado el uso de cookies se guarda 
 * la cookie que registra el consentimiento del usuario: "COOKIE_COMPLIANCE_ACCEPT".
 */
if (isset($_POST["COOKIE_COMPLIANCE_ACTION"]) && intval($_POST["COOKIE_COMPLIANCE_ACTION"]) == 0) {
    setcookie("COOKIE_COMPLIANCE_ACCEPT", "0", COOKIE_OPTIONS);
    $COOKIE_COMPLIANCE_ACCEPT = 0;
} elseif (isset($_POST["COOKIE_COMPLIANCE_ACTION"]) && intval($_POST["COOKIE_COMPLIANCE_ACTION"]) == 1) {
    setcookie("COOKIE_COMPLIANCE_ACCEPT", "1", COOKIE_OPTIONS);
    $COOKIE_COMPLIANCE_ACCEPT = 1;
}

// --- Constantes ---
define("E404_PAGE", "404.html");
define("PAGES_TO_SHOW", 2); // número de páginas a mostrar en la portada, "reciente"
define("POST_FOLDER", "pages/posts/"); // carpeta donde se guardan las páginas
define("COMMON_FOLDER", "pages/common/"); // carpeta donde se guardan las páginas
define("POST_FILENAMES", get_filenames(POST_FOLDER)); // obtenemos todas las páginas de la carpeta POST_FOLDER

define("DEF_TITLE_SUFFIX", " - record.rat.la"); // sufijo por defecto del título de la página
define("DEF_PAGE_IMG", "img/article_default_img_peach.jpg"); // imagen por defecto del artículo
define("DEF_AUTHOR_USER_NAME", "anon"); // datos de autor por defecto
define("DEF_DATETIME_FORMAT", "Y/m/d \· H:i"); // formato de fecha a mostrar una página (https://www.php.net/manual/es/function.date.php)
define("DEF_DATETIME_TIMEZONE", "Europe/Madrid"); // zona horaria en la que están escritos los artículos
// define("DEF_DATETIME_TIMEZONE_VISIBLE", "Europe/Madrid"); // @todo: considerar este comportamiento (zona horaria en la que se muestran las fechas)

define("MONTHS", ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"]);

// autor por defecto: Anon
define("AUTHORS", [
    DEF_AUTHOR_USER_NAME => new Author(DEF_AUTHOR_USER_NAME, "Anon", "anon.html"),
    "inoro" => new Author("inoro", "Inoro", "inoro.html")
]);

// --- Colores
$COLOR_ID = 0;

// foreground colors: 1D1313 000000 212228

$COLORS = [
    // Melocotón 2, electric boogaloo
    [
        "background" => "#f6f5e3",
        "text" => "#000000",
        "title" => "#000000",
        "link" => "#000000", // 0000EE 0000ff
        "link_visited" => "#000000", // 551A8B 483d8b 800080
        "link_active" => "#ff4500", // EE0000 ff4500
        "code_background" => "#F8F8F8",
        "code_text" => "#000000"
    ]
];

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

/**
 * get_author_by_user_name
 */
function get_author_by_user_name(string $user_name) : Author {
    if (isset(AUTHORS[$user_name])) {
        return AUTHORS[$user_name];
    }
    return AUTHORS[DEF_AUTHOR_USER_NAME];
}

/**
 * format_pretty_datetime
 */
function format_pretty_datetime(DateTime $datetime) : string {
    return sprintf(
        '%s de %s de %s a las %s horas',
        date_format($datetime, 'j'),
        strtolower(MONTHS[intval(date_format($datetime, 'm')) - 1]),
        date_format($datetime, 'Y'),
        date_format($datetime, 'H:i')
    );
}

/**
 * get_base_uri
 */
function get_base_uri() : string {
    $protocol = "http";
    // Detectar HTTPS de forma directa
    if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on') {
        $protocol = "https";
    }
    // Detectar HTTPS a través del header X-Forwarded-Proto (proxy_pass)
    if(isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === 'https') {
        $protocol = "https";
    }
    // Detectar HTTPS a través del header X-Url-Scheme (proxy_pass)
    if(isset($_SERVER["HTTP_X_URL_SCHEME"]) && $_SERVER["HTTP_X_URL_SCHEME"] === 'https') {
        $protocol = "https";
    }
    return $protocol . "://" . $_SERVER["HTTP_HOST"];
}

/**
 * get_full_uri
 */
function get_full_uri() : string {
    return get_base_uri() . $_SERVER["REQUEST_URI"];
}

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
 * get_sorted_post_arr
 */
function get_sorted_post_arr() : array {
    // creamos $fileInfoArr y $datetimeArr previamente para ordenar 
    // los archivos por fecha
    $post_arr = [];
    $publication_datetime_arr = [];
    foreach(POST_FILENAMES as $filename) {
        $post = new ContentPage(POST_FOLDER . $filename, get_base_uri() . "/show?filename=" . $filename);
        array_push($post_arr, $post);
        array_push($publication_datetime_arr, $post->get_publication_datetime());
    }
    // en base a los dos arrays anteriores ordeno por fecha
    array_multisort($publication_datetime_arr, SORT_DESC, $post_arr);
    return $post_arr;
}

// --- Clases ---

class Author {

    public readonly string $user_name;
    public readonly string $real_name;
    public readonly string $page_file_name;
    public readonly string $page_file_path;
    public readonly string $page_url;

    public function __construct(string $user_name, string $real_name, string $page_file_name) {
        $this->user_name = $user_name;
        $this->real_name = $real_name;
        $this->page_file_name = $page_file_name;
        $this->page_file_path = COMMON_FOLDER . $page_file_name;
        $this->page_url = get_base_uri() . "/author?username=" . $user_name;
    }

}


interface HtmlInteractor {

    public function get_title() : string;
    public function get_html_title() : string;
    public function get_description() : string;
    public function get_canonical_url() : string;
    public function get_og_type() : string;
    public function get_cover_img_url() : string;
    public function get_cover_img_mime_type() : string;
    public function get_cover_img_width() : string;
    public function get_cover_img_height() : string;
    public function get_content_to_print() : string;

}


abstract class GeneratedPage implements HtmlInteractor {

    protected string $title;
    protected string $description;
    protected string $og_type = "website";

    abstract protected function get_generated_content();

    public function get_title() : string {
        return $this->title;
    }

    public function get_html_title() : string {
        return $this->title . DEF_TITLE_SUFFIX;
    }

    public function get_description() : string {
        return $this->description;
    }
    
    public function get_canonical_url() : string {
        return get_full_uri();
    }

    public function get_og_type() : string {
        return $this->og_type;
    }

    public function get_cover_img_url() : string {
        return get_base_uri() . "/" . DEF_PAGE_IMG;
    }

    public function get_cover_img_mime_type() : string {
        return mime_content_type(DEF_PAGE_IMG);
    }

    public function get_cover_img_width() : string {
        // recordatorio: list($width, $height, $type, $attr) = getimagesize(DEF_PAGE_IMG);
        return getimagesize(DEF_PAGE_IMG)[0];
    }

    public function get_cover_img_height() : string {
        // recordatorio: list($width, $height, $type, $attr) = getimagesize(DEF_PAGE_IMG);
        return getimagesize(DEF_PAGE_IMG)[1];
    }

    public function get_content_to_print() : string {
        return $this->get_generated_content();
    }

}


class HomePage extends GeneratedPage {

    public function __construct() {
        $this->title = "Publicaciones recientes";
        $this->description = "Bienvenido a record.rat.la, donde un servidor, junto a las ratas del cementerio de Salem, registran sus desvaríos mentales.";
    }

    function get_generated_content() : string {
        $post_arr = get_sorted_post_arr();
        $content = "";

        $content .= "<h1>Publicaciones recientes</h1>\n";
        $content .= "
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
        foreach($post_arr as $post) {
            $post_content = reduce_h1(convert_title_to_link(
                $post->get_file_name(),
                $post->get_title(),
                $post->get_content_to_print()
            ));
            $content .= "<article>\n" . $post_content . "\n</article>\n";
            if ($number >= PAGES_TO_SHOW) {
                break;
            }
            $number++;
        }
        return $content;
    }

}


class ArchivePage extends GeneratedPage {

    public function __construct() {
        $this->title = "Historias de una rata";
        $this->description = "Registro cronológico de todas las publicaciones de la web.";
    }

    function get_generated_content() : string {
        $post_arr = get_sorted_post_arr();
        $content = "";

        $current_year = "";
        $current_month = "";

        $content .= "<h1>Historias de una rata</h1>\n";
        $content .= "<p>Registro cronológico de todas las publicaciones de la web.</p>\n";
    
        foreach($post_arr as $post) {
            $year = date_format($post->get_publication_datetime(), "Y");
            $month = date_format($post->get_publication_datetime(), "n"); // n: 1..12 / m: 01..12
            $day_hour = date_format($post->get_publication_datetime(), "d \· H:i");
    
            if ($current_year != $year) {
                $current_year = $year;
                $content .= sprintf("<h2>– Año %s –</h2>\n", $year);
            }
    
            if ($current_month != $month) {
                $current_month = $month;
                $content .= sprintf("<h3>%s</h3>\n", MONTHS[intval($month) - 1]);
            }
    
            $content .= sprintf(
                '<blockquote>%s · <strong><a href="show?filename=%s">%s</a></strong><br>%s</blockquote>' . "\n",
                $day_hour,
                $post->get_file_name(),
                $post->get_title(),
                $post->get_description()
            );
        }
    
        $content .= sprintf("<p>Hay un total de %d páginas en la web.</p>\n", count(POST_FILENAMES));
        return $content;
    }

}


class ContentPage implements HtmlInteractor {

    // File properties
    private string $file_path;
    private string $file_name;
    private string $file_content;

    // Basic properties
    private string $title;
    private string $url;
    private string $description;
    private Author $author;
    private DateTime $publication_datetime;
    private string $og_type = "article";

    // Extended properties
    // private string $cover_img;
    // private string $structured_data_json;
    private mixed $modification_datetime;

    public function __construct(string $file_path, string $url) {
        $this->file_path = $file_path;
        $this->file_name = basename($file_path);
        $this->file_content = file_get_contents($file_path) ?: "Empty page";

        $this->title = $this->parse_title();
        $this->url = $url;
        $this->canonical_url = $this->parse_title();
        $this->description = $this->parse_description();
        $author_user_name = $this->parse_author_user_name();
        $this->author = get_author_by_user_name($author_user_name);
        $this->publication_datetime = $this->parse_publication_datetime();
        $this->modification_datetime = $this->parse_modification_datetime();
    }

    // --- Parsers

    /**
     * parse_title, obtiene el título (<h1></h1>) de la página en base a su 
     * contenido. Luego elimina los tags HTML y espacios sobrantes que posea 
     * en su interior
     */
    private function parse_title() : string {
        preg_match_all("/<h1>(.*)<\/h1>/i", $this->file_content, $matches, PREG_PATTERN_ORDER);
        /**
         * quitamos las tags HTML, los espacios sobrantes y luego cambiamos los 
         * caracteres especiales por sus códigos HTML (incluidas las " y ')
         */
        return htmlentities(trim(strip_tags($matches[1][0])), ENT_QUOTES); 
    }

    /**
     * parse_description, obtiene el contenido del primer párrafo <p></p> del
     * contenido, asumiendo que es la descripción. Además ajusta su tamaño 
     * para que no exceda los 160 caracteres recomendados para el SEO
     * 
     * @todo: mejorar la eficiencia de esta función
     * 
     */
    private function parse_description() : string {
        $content = $this->file_content;
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
     * parse_author_user_name, obtiene el user_name del author a partir del 
     * contenido
     */
    private function parse_author_user_name() : string {
        $regex = '/<!-- author (.*) -->/';
        $matches_count = preg_match_all($regex, $this->file_content, $matches, PREG_PATTERN_ORDER);
        if ($matches_count != 0 && isset(AUTHORS[$matches[1][0]])) {
            return $matches[1][0];
        }
        return DEF_AUTHOR_USER_NAME;
    }

    /**
     * parse_publication_datetime, obtiene la fecha de publicación del 
     * artículo en base al comentario "publication_datetime"
     */
    private function parse_publication_datetime() : DateTime {
        $regex = '/<!-- publication_datetime (\d{4})(\d{2})(\d{2})T(\d{2})(\d{2}) -->/';
        $matches_count = preg_match_all($regex, $this->file_content, $matches, PREG_PATTERN_ORDER);

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

        $datetime_str = $year . "/" . $month . "/" . $day . " " . $hour . ":" . $minute;
        $datetime_obj = date_create($datetime_str, new DateTimeZone(DEF_DATETIME_TIMEZONE));

        // si la fecha no es válida, se devuelve una válida
        if ($datetime_obj == null) {
            $datetime_obj = new DateTime();
        }

        return $datetime_obj;
    }

    /**
     * parse_modification_datetime, obtiene la fecha de modificación del 
     * artículo en base al comentario "modification_datetime"
     */
    private function parse_modification_datetime() : DateTime | null {
        $regex = '/<!-- modification_datetime (\d{4})(\d{2})(\d{2})T(\d{2})(\d{2}) -->/';
        $matches_count = preg_match_all($regex, $this->file_content, $matches, PREG_PATTERN_ORDER);

        if ($matches_count != 0) {
            $year = $matches[1][0];
            $month = $matches[2][0];
            $day = $matches[3][0];
            $hour = $matches[4][0];
            $minute = $matches[5][0];

            $datetime_str = $year . "/" . $month . "/" . $day . " " . $hour . ":" . $minute;
            $datetime_obj = date_create($datetime_str, new DateTimeZone(DEF_DATETIME_TIMEZONE));
    
            return $datetime_obj;
        }
        return null;
    }

    // --- Basic properties getters

    function get_file_path() : string { return $this->file_path; }
    function get_file_name() : string { return $this->file_name; }
    function get_file_content() : string { return $this->file_content; }

    function get_title() : string { return $this->title; }
    function get_html_title() : string { return $this->title . DEF_TITLE_SUFFIX; }
    function get_url() : string { return $this->url; }
    function get_canonical_url() : string { return $this->get_url(); }
    function get_description() : string { return $this->description; }
    function get_author() : Author { return $this->author; }
    function get_publication_datetime() : DateTime { return $this->publication_datetime; }
    function get_publication_datetime_w3c() : string { return date_format($this->publication_datetime, DATE_W3C); }
    function get_publication_datetime_iso8601() : string { return date_format($this->publication_datetime, DATE_ISO8601); }
    function get_modification_datetime() : DateTime { return $this->modification_datetime; }
    function get_og_type() : string { return $this->og_type; }

    // --- Extended properties getters

    /**
     * get_cover_img_relative_path, obtiene la primera imagen mostrada en el contenido, 
     * si no hay ninguna se utiliza la imagen por defecto
     */
    function get_cover_img_relative_path() : string {
        preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $this->file_content, $matches);
        if (isset($matches["src"])) {
            return $matches["src"];
        }
        return DEF_PAGE_IMG;
    }

    function get_cover_img_url() : string {
        return get_base_uri() . "/" . $this->get_cover_img_relative_path();
    }

    function get_cover_img_mime_type() : string {
        return mime_content_type($this->get_cover_img_relative_path());
    }

    public function get_cover_img_width() : string {
        // recordatorio: list($width, $height, $type, $attr) = getimagesize($this->get_cover_img_relative_path());
        return getimagesize($this->get_cover_img_relative_path())[0];
    }

    public function get_cover_img_height() : string {
        // recordatorio: list($width, $height, $type, $attr) = getimagesize($this->get_cover_img_relative_path());
        return getimagesize($this->get_cover_img_relative_path())[1];
    }

    /**
     * get_content_to_print
     */
    function get_content_to_print() : string {
        $content = $this->file_content;
        $content .= sprintf(
                "\n" . '<p style="text-align:left;"><small>† Publicado por <a href="author?username=%s" aria-label="Página del autor %s.">%s</a> el %s',
                $this->get_author()->user_name,
                $this->get_author()->real_name,
                $this->get_author()->real_name,
                format_pretty_datetime($this->get_publication_datetime())
        );
        if ($this->has_modification_datetime()) {
            $content .= sprintf(
                ' y se ha revisado por última vez el %s',
                format_pretty_datetime($this->get_modification_datetime())
            );
        }
        $content .= "</small></p>\n";
        return $content;
    }

    // @todo: refactor
    // function get_structured_data_json(array $pageInfo, string $canonical_url, string $page_url) : string {
    function get_structured_data_json() : string {
        return json_encode([
            "@context" => "https://schema.org/",
            "@type" => "BlogPosting",
            "@id" => $this->get_url(),
            // "mainEntityOfPage" => "https://dataliberate.com/2019/05/14/library-metadata-evolution-final-mile/",
            "headline" => $this->get_title(),
            "name" => $this->get_title(),
            "description" => $this->get_description(),
            "datePublished" => $this->get_publication_datetime_iso8601(),
            // "dateModified" => "2019-05-14",
            "author" => [
                "@type" => "Person",
                "@id" => $this->get_author()->page_url,
                "name" => $this->get_author()->real_name,
                "url" => $this->get_author()->page_url,
                // "image" => [
                //     "@type" => "ImageObject",
                //     "@id" => "https://secure.gravatar.com/avatar/bbdd78abba6116d6f5bfa2c992de6592?s=96&d=mm&r=g",
                //     "url" => "https://secure.gravatar.com/avatar/bbdd78abba6116d6f5bfa2c992de6592?s=96&d=mm&r=g",
                //     "height" => "96",
                //     "width" => "96"
                // ]
            ],
            // "publisher" => [
            //     "@type" => "Organization",
            //     "@id" => "https://dataliberate.com",
            //     "name" => "Data Liberate",
            //     "logo" => [
            //         "@type" => "ImageObject",
            //         "@id" => "https://dataliberate.com/wp-content/uploads/2011/12/Data_Liberate_Logo-200.png",
            //         "url" => "https://dataliberate.com/wp-content/uploads/2011/12/Data_Liberate_Logo-200.png",
            //         "width" => "600",
            //         "height" => "60"
            //     ]
            // ],
            // "image" => [
            //     "@type" => "ImageObject",
            //     "@id" => $this->get_cover_img_url(),
            //     "url" => $this->get_cover_img_url(),
            //     "height" => "362",
            //     "width" => "388"
            // ],
            "image" => [$this->get_cover_img_url()],
            "url" => $this->get_url(),
            "isPartOf" => [
                "@type" => "Blog",
                "@id" => "https://record.rat.la/",
                "name" => "record.rat.la",
                // "publisher" => [
                //     "@type" => "Organization",
                //     "@id" => "https://dataliberate.com",
                //     "name" => "Data Liberate"
                // ]
                "author" => [
                    "@type" => "Person",
                    "@id" => "https://record.rat.la/author?username=inoro",
                    "name" => "Inoro",
                    "url" => "https://record.rat.la/author?username=inoro",
                    // "image" => [
                    //     "@type" => "ImageObject",
                    //     "@id" => "https://secure.gravatar.com/avatar/bbdd78abba6116d6f5bfa2c992de6592?s=96&d=mm&r=g",
                    //     "url" => "https://secure.gravatar.com/avatar/bbdd78abba6116d6f5bfa2c992de6592?s=96&d=mm&r=g",
                    //     "height" => "96",
                    //     "width" => "96"
                    // ]
                ]
            ],
            // "wordCount" => "488",
            // "keywords" => [
            //     "Bibframe2Schema.org",
            //     "Libraries",
            //     "Library of Congress"
            // ],
            // "aggregateRating" => [
            //     "@type" => "AggregateRating",
            //     "@id" => "https://dataliberate.com/2019/05/14/library-metadata-evolution-final-mile/#aggregate",
            //     "url" => "https://dataliberate.com/2019/05/14/library-metadata-evolution-final-mile/",
            //     "ratingValue" => "2.5",
            //     "ratingCount" => "2"
            // ]
        ]);
    }

    function has_modification_datetime() : bool {
        return $this->modification_datetime != null;
    }

}


// --- Variables globales ---
$ACTION = 0;
$page;

// --- Lógica de rutas (Ingress) ---
// route the request internally
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ('/' === $uri) {
    $page = new HomePage();
} elseif ('/archive' === $uri) {
    $page = new ArchivePage();
} elseif ('/show' === $uri && isset($_GET['filename'])) {
    if (in_array($_GET['filename'], POST_FILENAMES)) {
        // Post
        $page = new ContentPage(POST_FOLDER . $_GET['filename'], get_full_uri());
    } else {
        // Error 404 (Post not found)
        $ACTION = 404;
    }
} elseif ('/author' === $uri && isset($_GET['username'])) {
    if (isset(AUTHORS[$_GET['username']])) {
        // Author page
        $page = new ContentPage(
            COMMON_FOLDER . get_author_by_user_name($_GET['username'])->page_file_name,
            get_full_uri()
        );
    } else {
        // Error 404 (Username not found)
        $ACTION = 404;
    }
} elseif ('/faq' === $uri) {
    $page = new ContentPage(COMMON_FOLDER . "faq.html", get_full_uri());
} elseif ('/donations' === $uri) {
    $page = new ContentPage(COMMON_FOLDER . "donaciones.html", get_full_uri());
} elseif ('/description' === $uri) {
    $page = new ContentPage(COMMON_FOLDER . "descripcion.html", get_full_uri());
} elseif ('/cookie' === $uri) {
    $page = new ContentPage(COMMON_FOLDER . "cookie.html", get_full_uri());
} elseif ('/sitemapgen' === $uri) {
    // only for cicd tools
    if (file_exists("generate-sitemap.php")) {
        include "generate-sitemap.php";
        die();
    }
    $ACTION = 404;
} else {
    // Error 404 (Page not found)
    $ACTION = 404;
}

if ($ACTION == 404) {
    $page = new ContentPage(COMMON_FOLDER . E404_PAGE, get_full_uri());
    http_response_code(404);
}

?>
<!DOCTYPE html>
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
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?= $page->get_html_title() ?></title>

        <!-- ## LINK ## -->
        <!-- Aunque a Firefox no le haga falta este tag, a Google si (SEO) -->
        <link rel="icon" href="favicon.ico">

        <!-- Para decirle a google que la URL original es esta, y no la que se está usando -->
        <link rel="canonical" href="<?= $page->get_canonical_url() ?>">

        <!-- Para decirle al navegador que tengo RSS -->
        <link rel="alternate" type="application/rss+xml" href="rss.xml" title="RSS de record.rat.la">

        <!-- Avisamos al navegador de que se prepare para hacer una petición a los siguientes dominios -->
<?php if ($COOKIE_COMPLIANCE_ACCEPT == 1) { ?>
        <link rel="preconnect" href="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ">
        <link rel="dns-prefetch" href="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ">
<?php } ?>
        <link rel="preload" href="res/eb-garamond/EBGaramond-Regular.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-Italic.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-Bold.ttf" as="font" type="font/ttf" crossorigin>
        <link rel="preload" href="res/eb-garamond/EBGaramond-BoldItalic.ttf" as="font" type="font/ttf" crossorigin>

        <!-- ## META ## -->
        <!-- Revisar: https://css-tricks.com/essential-meta-tags-social-media/ -->
        <meta name="title" content="<?= $page->get_title() ?>">
        <meta name="description" content="<?= $page->get_description() ?>">
        <meta name="author" content="Inoro"> <!-- This site was made by https://github.com/1noro -->
        <meta name="theme-color" content="#000000"> <!-- Para que el navegador sepa que color debe usar en el marco -->

        <!-- OG -->
        <meta property="og:type" content="<?= $page->get_og_type() ?>">
<?php if ($page instanceof ContentPage) { ?>
        <meta property="article:author" content="<?= $page->get_author()->page_url ?>">
        <meta property="article:published_time" content="<?= $page->get_publication_datetime_w3c() ?>">
        <!-- <meta property="article:modified_time" content="2020-09-21T07:23:04+00:00"> -->
<?php } ?>
        <meta property="og:url" content="<?= $page->get_canonical_url() ?>">
        <meta property="og:site_name" content="record.rat.la">
        <meta property="og:locale" content="es_ES">
        <meta property="og:title" content="<?= $page->get_title() ?>">
        <meta property="og:description" content="<?= $page->get_description() ?>">
        <meta property="og:image" content="<?= $page->get_cover_img_url() ?>">
        <meta property="og:image:alt" content="Imagen de portada.">
        <meta property="og:image:type" content="<?= $page->get_cover_img_mime_type() ?>">
        <meta property="og:image:width" content="<?= $page->get_cover_img_width() ?>">
        <meta property="og:image:height" content="<?= $page->get_cover_img_height() ?>">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="<?= $page->get_canonical_url() ?>">
        <meta property="twitter:title" content="<?= $page->get_title() ?>">
        <meta property="twitter:description" content="<?= $page->get_description() ?>">
        <meta property="twitter:image" content="<?= $page->get_cover_img_url() ?>">
        <!-- <meta name="twitter:image:src" content=""> -->
        <!-- <meta name="twitter:creator" content="@example"> -->
        <!-- <meta name="twitter:site" content="cuenta_del_sitio"> -->

        <!-- Scraping -->
        <meta name="robots" content="index, follow">
        <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

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
                vertical-align: -0.7px;
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

            fieldset#cookie_compliance_notice {
                max-width: 400px;
                position: fixed;
                bottom: 0px;
                right: 0px;
                margin: 10px 10px;
                background-color: <?= $COLORS[$COLOR_ID]["code_background"] ?>;
                font-size: medium;
                text-align: left;
                padding: 10px 25px;
            }

            fieldset#cookie_compliance_notice nav {
                display: flex;
                justify-content: space-around;
                margin-bottom: 16px;
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

<?php if ($page instanceof ContentPage) { ?>
        <script type="application/ld+json"><?= $page->get_structured_data_json() ?></script>
<?php } ?>
<?php if ($COOKIE_COMPLIANCE_ACCEPT == 1) { ?>
        <!-- Cosas de la NSA (en modo prueba) -->
        <!-- Google Analytics -->
        <!-- La sitúo aquí para mejorar la carga de la web -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-W3KC9CP7ZQ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-W3KC9CP7ZQ');
        </script>
<?php } ?>
    </head>

    <body>
        
        <header id="header" aria-label="Cabecera" tabindex="-1">
            <!-- Barra de accesibilidad -->
            <nav aria-label="Enlaces de control de la web" style="text-align: left;">
                <a href="#main">ir al contenido</a>&nbsp;&nbsp;&nbsp;<a href="#footer">ir al pié</a>
            </nav>
            <!-- Título del HEADER -->
            <div id="web_title">
                record <a href="https://youtu.be/dQw4w9WgXcQ" style="text-decoration: none;" aria-label="El enlace perfecto"><svg version="1.1" id="title_rat" width="43.916821" height="48" viewBox="0 0 43.916821 48" xmlns="http://www.w3.org/2000/svg">
                    <defs id="defs6" />
                    <g id="g8" transform="translate(-85.151406,-91.50935)">
                        <path style="stroke-width:0.284751" d="m 96.888966,138.9539 c -2.150695,-0.68203 -4.840339,-2.39978 -6.548236,-4.18205 -2.343726,-2.44579 -4.095828,-7.03549 -4.106657,-10.75755 -0.01168,-4.01519 1.083174,-9.33582 2.682109,-13.03413 l 0.97957,-2.26574 -0.886563,-0.74599 c -1.352099,-1.13771 -2.940944,-3.80218 -3.50096,-5.87105 -0.532955,-1.96889 -0.460494,-5.393697 0.161161,-7.617208 0.39468,-1.411677 1.519512,-2.70073 2.555153,-2.928195 1.061284,-0.233097 3.772964,0.523227 5.193355,1.448497 1.271763,0.828452 2.796386,3.047438 3.664966,5.334114 0.590332,1.554142 0.674654,1.643501 1.365421,1.446974 0.947684,-0.26962 3.372295,-0.26962 4.319985,0 0.69076,0.196527 0.77508,0.107168 1.36542,-1.446974 0.86858,-2.286676 2.3932,-4.505662 3.66496,-5.334114 1.42039,-0.92527 4.13207,-1.681594 5.19336,-1.448497 1.03564,0.227465 2.16047,1.516518 2.55515,2.928195 0.62166,2.223511 0.69412,5.648318 0.16116,7.617208 -0.56001,2.06887 -2.14886,4.73334 -3.50096,5.87105 l -0.88656,0.74599 0.97957,2.26574 c 1.30107,3.00935 2.04854,6.03628 2.45609,9.94614 0.47184,4.52659 0.0845,6.79379 -1.95103,11.41853 -0.21944,0.49858 1.60114,1.20863 3.09909,1.20868 l 1.21934,5e-5 0.0134,-4.91201 c 0.0123,-4.50115 0.0781,-5.12637 0.78607,-7.47477 1.8027,-5.97925 5.43343,-11.53244 7.54004,-11.53244 1.15458,0 1.89484,0.72729 2.8554,2.80539 0.52921,1.1449 0.74508,2.08828 0.74927,3.27432 0.005,1.47373 -0.0858,1.74492 -0.78833,2.3492 -0.43681,0.37573 -1.02913,0.68314 -1.31628,0.68314 -0.89007,0 -1.78552,-0.96884 -1.99015,-2.15327 l -0.19372,-1.12137 -0.77343,1.49862 c -2.25465,4.36863 -3.16933,9.2335 -2.69057,14.31027 0.35942,3.8114 -0.006,4.86517 -2.05607,5.9256 -1.82277,0.94295 -4.92518,0.74769 -7.53663,-0.47436 l -1.84204,-0.86199 -1.86963,1.25372 c -3.4648,2.32341 -7.48243,2.98455 -11.122234,1.83028 z m -0.238652,-20.42542 c 1.005346,-0.67129 1.36018,-3.90531 0.520604,-4.74489 -1.106329,-1.10633 -2.411026,-0.0642 -2.618133,2.09117 -0.117935,1.22737 -0.04217,1.49826 0.598082,2.13852 0.775856,0.77586 0.995865,0.85145 1.499447,0.5152 z m 9.418016,-0.51785 c 0.65782,-0.65781 0.71771,-0.88822 0.56535,-2.17469 -0.25831,-2.18101 -1.48589,-3.1545 -2.58805,-2.05235 -0.83957,0.83958 -0.48474,4.0736 0.52061,4.74489 0.50381,0.33641 0.72362,0.26063 1.50209,-0.51785 z" id="path170" />
                    </g>
                </svg></a> la
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
<?php if ($COOKIE_COMPLIANCE_ACCEPT != 0 && $COOKIE_COMPLIANCE_ACCEPT != 1) { ?>
            <!-- Alerta sobre las cookies -->
            <fieldset id="cookie_compliance_notice">
                <p>
                    &dagger; Esta web utiliza cookies propias para la personalización, y otras de terceros para obtener datos estadísticos de la navegación de los usuarios. Puedes <!--cambiar la configuración u--> obtener <a href="cookie" aria-label="¡Infórmate sobre las cookies!">más información aquí</a>.
                </p>
                <nav aria-label="Botones de consentimiento de cookies">
                    <form action="<?= $page->get_canonical_url() ?>" method="post">
                        <input type="hidden" name="COOKIE_COMPLIANCE_ACTION" value="1">
                        <button type="submit">Acepto</button>
                    </form>
                    <form action="<?= $page->get_canonical_url() ?>" method="post">
                        <input type="hidden" name="COOKIE_COMPLIANCE_ACTION" value="0">
                        <button type="submit">No acepto</button>
                    </form>
                    <span>
                        <button onclick="document.getElementById('cookie_compliance_notice').style.display = 'none';">Ocultar</button>
                    </span>
                </nav>
            </fieldset>
<?php } ?>
        </header>

        <main id="main" aria-label="Contenido principal" tabindex="-1">
            <?= $page->get_content_to_print() ?>
        </main>

        <footer id="footer" aria-label="Licencias, contactos y más enlaces" tabindex="-1">
            <nav aria-label="Enlace al archivo de publicaciones">
                <p>
                    <a href="archive">&laquo;más publicaciones&raquo;</a>
                </p>
            </nav>
            <nav aria-label="Moverse por esta página">
                <p>
                    <a href="#header">ir a la cabecera</a>&nbsp;&nbsp;&nbsp;<a href="#main">ir al contenido</a>
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
