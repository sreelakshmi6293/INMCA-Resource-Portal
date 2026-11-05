from db_config import get_db_connection

try:
    conn = get_db_connection()
    print("Database connected successfully!")

    cursor = conn.cursor()
    cursor.execute("SHOW TABLES")

    print("Tables in student_portal:")
    for table in cursor.fetchall():
        print(table[0])

    cursor.close()
    conn.close()

except Exception as e:
    print("Database connection failed:")
    print(e)
