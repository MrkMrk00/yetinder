from get_connection import Connection
from names import get_first_name
from random import randint


def generate_query(count: int,
                   created_by_id: int,
                   colors: list[int]) -> str:
    query = 'INSERT INTO yeti (color_id, name, weight, height, age, created_by_id, sex) VALUES '

    for i in range(count):
        color = colors[randint(0, len(colors) - 1)]
        sex = 'male' if i < count // 2 else 'female'
        name = get_first_name(sex)
        weight = randint(51, 1999)
        height = randint(51, 349)
        age = randint(1, 119)

        query += f"({color}, '{name}', {weight}, {height}, {age}, {created_by_id}, '{sex}'){', ' if i != count - 1 else ';'}"

    return query


def main(count=10, created_by_id=1):
    with Connection() as con:
        with con.cursor() as cur:
            cur.execute('''
            SELECT DISTINCT id
                FROM color;
            ''')
            colors = list(map(lambda col_obj: col_obj[0], cur.fetchall()))

        with con.cursor() as cur:
            cur.execute(generate_query(count, created_by_id, colors))

        con.commit()


if __name__ == '__main__':
    main()
