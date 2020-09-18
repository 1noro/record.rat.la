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
                <a href="#" title="Los últimos posts">reciente</a> / <a href="#" title="Todos los post ordenados por fecha">histórico</a> / <a href="#" title="¿Qué es esta página?">faq</a>
            </p>
        </header>

        <div id="content">
            <?php echo file_get_contents('article/202009181457-genesis.html'); ?>
            <hr>
            <?php echo file_get_contents('article/202009181510-descripcion.html'); ?>
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
