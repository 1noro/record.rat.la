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
PAGES_IN_FEED = 10

AUTHORS = {
    "anon": ["Anon", "404.html"], 
    "inoro": ["Inoro", "inoro.html"]
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

# def parse_title(line):
#     line = line.replace("<h1>", "")
#     line = line.replace("</h1>", "")
#     line = line.strip()

#     # borramos los tags HTML
#     regex = re.compile('<.*?>')
#     title = re.sub(regex, '', line)

#     return title

def get_author(content):
    regex = r'<!-- author (.*) -->'
    match = re.search(regex, content)
    print(match)
    return match.group(1)

def get_title(content):
    regex = r'<h1>(.*)<\/h1>'
    match = re.search(regex, content)
    # borramos los tags HTML
    return re.sub(re.compile('<.*?>'), '', match.group(1))

# --- Main
pagename_list = [f for f in listdir(PAGES_FOLDER) if isfile(join(PAGES_FOLDER, f))]

print("Procesando todas las páginas")
pages_list = []
for pagename in pagename_list:
    page_path = PAGES_FOLDER + pagename
    print(">> " + page_path)
    with open(page_path, "r") as f:
        content = f.read()
        # publication_datetime = get_publication_datetime(content)
        author = get_author(content)
        title = get_title(content)
        pages_list.append({
            "pagename": pagename,
            "title": title,
            "pubDate": datetime.now(),
            "author": author
        })

# ordenamos por fecha (de nuevo > viejo)
sorted_pages_list = sorted(pages_list, key=itemgetter('pubDate'), reverse=True)

# escribimos el RSS.XML
print("Generando rss.xml con las {} mas nuevas".format(PAGES_IN_FEED))
with open(RSS_FILE_PATH, "w") as f:
    f.write('''<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>record.rat.la</title>
        <description>Blog/web personal donde iré registrando mis proyectos y mis líos mentales.</description>
        <language>es-es</language>
        <link>https://record.rat.la/</link>
        <atom:link href="https://record.rat.la/rss.xml" rel="self" type="application/rss+xml"/>
        <image>
            <title>record.rat.la</title>
            <url>https://record.rat.la/favicon.png</url>
            <link>https://record.rat.la/</link>
        </image>\n''')

    # f.write('<item></item>')
    for page in sorted_pages_list[:PAGES_IN_FEED]:
        page_path = PAGES_FOLDER + page['pagename']
        print(">> " + page_path)
        with open(page_path, 'r') as page_file:
            page_content = page_file.read()
            page_content = page_content.replace('"img/', '"https://record.rat.la/img/')
            # NOTA: el 'author' debe ser un email
            f.write('''
<item>
    <title>{}</title>
    <guid>https://record.rat.la/index.php?page={}</guid>
    <link>https://record.rat.la/index.php?page={}</link>
    <!--<author>{}</author>-->
    <pubDate>{}</pubDate>
    <description>
<![CDATA[{}]]>
    </description>
</item>
'''.format(page['title'], page['pagename'], page['pagename'], page['author'], utils.format_datetime(page['pubDate']), page_content))

    f.write('''
    </channel>
</rss>''')
