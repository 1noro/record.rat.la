#!/usr/bin/python

from os import listdir
from os.path import isfile, join

from datetime import datetime
import time
from email import utils
import pytz

import re

from operator import itemgetter

# --- Global
PAGES_FOLDER = "public/pages/"
RSS_FILE_PATH = "public/rss.xml"
PAGES_IN_FEED = 5

AUTHORS = {
    "a": ["Anon", "404.html"], 
    "i": ["Inoro", "inoro.html"]
}

TIMEZONE = "Europe/Madrid"

# --- Functions
def parse_data(line):
    line = line.replace("<!--", "")
    line = line.replace("-->", "")
    line = line.strip()

    datetime_str = line[:-1]
    # fecha_str en formato: YYYYmmddHHMM
    datetime_obj = datetime.strptime(datetime_str, '%Y%m%d%H%M')
    timezone = pytz.timezone(TIMEZONE)
    datetime_obj = timezone.localize(datetime_obj)

    author_key = line[-1]

    # (pubDate, author)
    # return (utils.format_datetime(datetime_obj), AUTHORS[author_key][0])
    return (datetime_obj, AUTHORS[author_key][0])

def parse_title(line):
    line = line.replace("<h1>", "")
    line = line.replace("</h1>", "")
    line = line.strip()

    # borramos los tags HTML
    regex = re.compile('<.*?>')
    title = re.sub(regex, '', line)

    return title

# --- Main
pagename_list = [f for f in listdir(PAGES_FOLDER) if isfile(join(PAGES_FOLDER, f))]

print("Procesando todas las páginas")
pages_list = []
for pagename in pagename_list:
    page_path = PAGES_FOLDER + pagename
    print(">> " + page_path)
    with open(page_path, "r") as f:
        pub_date, author = parse_data(f.readline())
        title = parse_title(f.readline())
        pages_list.append({
            "pagename": pagename,
            "title": title,
            "pubDate": pub_date,
            "author": author
        })

# ordenamos por fecha (de nuevo > viejo)
sorted_pages_list = sorted(pages_list, key=itemgetter('pubDate'), reverse=True)

# escribimos el RSS.XML
print("Generando rss.xml con las {} mas nuevas".format(PAGES_IN_FEED))
with open(RSS_FILE_PATH, "w") as f:
    f.write('''<?xml version="1.0" ?>
<rss version="2.0">
    <channel>
        <title>record.rat.la</title>
        <link>https://record.rat.la/</link>
        <description>Blog/web personal donde iré registrando mis proyectos y mis líos mentales.</description>
        <image>
            <url>https://record.rat.la/favicon.webp</url>
            <link>https://record.rat.la/index.php</link>
        </image>\n''')

    # f.write('<item></item>')
    for page in sorted_pages_list[:PAGES_IN_FEED]:
        page_path = PAGES_FOLDER + page['pagename']
        print(">> " + page_path)
        with open(page_path, 'r') as page_file:
            page_content = page_file.read()
            # NOTA: el 'author' debe ser un email
            f.write('''
<item>
    <title>{}</title>
    <link>https://record.rat.la/index.php?page={}</link>
    <!--<author>{}</author>-->
    <pubDate>{}</pubDate>
    <description>
<![CDATA[{}]]>
    </description>
</item>
'''.format(page['title'], page['pagename'], page['author'], utils.format_datetime(page['pubDate']), page_content))

    f.write('''
    </channel>
</rss>''')