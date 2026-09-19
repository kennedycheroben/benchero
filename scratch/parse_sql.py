import re
# pyrefly: ignore [missing-import]
import mysql.connector

# Read the raw prompt SQL directly
with open('scratch/user_prod.sql', 'r') as f:
    raw_sql = f.read()

# Connect to mysql
conn = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password=""
)
cursor = conn.cursor()

cursor.execute("DROP DATABASE IF EXISTS test_user_prod_schema")
cursor.execute("CREATE DATABASE test_user_prod_schema DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
cursor.execute("USE test_user_prod_schema")

# Execute multiline statements using mysql cursor
for stmt in raw_sql.split(';'):
    stmt_str = stmt.strip()
    # Skip comments
    clean_lines = [line for line in stmt_str.split('\n') if not line.strip().startswith('--') and not line.strip().startswith('/*')]
    clean_stmt = ' '.join(clean_lines).strip()
    if clean_stmt:
        try:
            cursor.execute(clean_stmt)
        except Exception as e:
            print(f"Error executing statement: {clean_stmt[:60]}... -> {e}")

conn.commit()

cursor.execute("SHOW TABLES")
tables = [row[0] for row in cursor.fetchall()]
print(f"Created {len(tables)} tables in test_user_prod_schema.")

cursor.close()
conn.close()
