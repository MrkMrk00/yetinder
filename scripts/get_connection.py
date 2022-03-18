from mysql.connector import connect
from dotenv import load_dotenv
from os import getenv
from urllib.parse import urlparse
from re import match


class Connection:
    def __init__(self):
        self.connection = _connect_to_database()

    def __enter__(self):
        return self.connection

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.connection.close()


def _connect_to_database():
    load_dotenv('../.env.local')
    db_url = getenv('DATABASE_URL', None)
    if db_url is None:
        raise Exception('Nepodarilo se nacist databazovy string')

    con_args = urlparse(db_url)
    params = match(r'(\w+):(.+)@([\w\.]+):([0-9]{2,6})', con_args.netloc)
    groups = params.groups()

    if len(groups) == 0:
        raise Exception('Nepodarilo se regexovat con string')

    return connect(
        user=groups[0],
        password=groups[1],
        host=groups[2],
        port=groups[3],
        database=con_args.path.replace('/', '')
    )
