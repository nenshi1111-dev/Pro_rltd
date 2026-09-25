import sqlite3
from tkinter import *
from tkinter import messagebox
from tkinter import ttk  # For Combobox and Treeview

def connect_db():
    conn = sqlite3.connect("users.db")
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT UNIQUE,
            age INTEGER,
            gender TEXT,
            city TEXT
        )
    """)
    conn.commit()
    conn.close()

def add_user():
    if not validate_inputs():
        return
    try:
        conn = sqlite3.connect("users.db")
        cursor = conn.cursor()
        cursor.execute("INSERT INTO users (name, email, age, gender, city) VALUES (?, ?, ?, ?, ?)", 
                    (name_var.get(), email_var.get(), int(age_var.get()), gender_var.get(), city_var.get()))
        conn.commit()
        conn.close()
        messagebox.showinfo("Success", "User added successfully!")
        clear_fields()
        load_users()  # Refresh table
    except sqlite3.IntegrityError:
        messagebox.showerror("Error", "Email already exists!")
    except Exception as e:
        messagebox.showerror("Error", str(e))

def update_user():
    if current_user_id is None:
        messagebox.showwarning("Error", "No user selected to update. Select a user first.")
        return
    if not validate_inputs():
        return
    try:
        conn = sqlite3.connect("users.db")
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE users
            SET name=?, email=?, age=?, gender=?, city=?
            WHERE id=?
        """, (name_var.get(), email_var.get(), int(age_var.get()), gender_var.get(), city_var.get(), current_user_id))
        conn.commit()
        conn.close()
        messagebox.showinfo("Success", "User updated successfully!")
        load_users()  # Refresh table
    except sqlite3.IntegrityError:
        messagebox.showerror("Error", "Email already exists!")
    except Exception as e:
        messagebox.showerror("Error", str(e))

def delete_user():
    if current_user_id is None:
        messagebox.showwarning("Error", "No user selected to delete. Select a user first.")
        return

    confirm = messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this user?")
    if confirm:
        conn = sqlite3.connect("users.db")
        cursor = conn.cursor()
        cursor.execute("DELETE FROM users WHERE id=?", (current_user_id,))
        conn.commit()
        conn.close()
        messagebox.showinfo("Deleted", "User deleted successfully!")
        clear_fields()
        load_users()  # Refresh table

def load_users():
    for row in tree.get_children():
        tree.delete(row)

    conn = sqlite3.connect("users.db")
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM users")
    rows = cursor.fetchall()
    conn.close()

    for row in rows:
        tree.insert("", END, values=row)

def clear_fields():
    global current_user_id
    current_user_id = None
    name_var.set("")
    email_var.set("")
    age_var.set("")
    gender_var.set("Male")
    city_var.set("")
    tree.selection_remove(tree.selection())

def on_tree_select(event):
    selected = tree.focus()
    if selected:
        values = tree.item(selected, 'values')
        global current_user_id
        current_user_id = values[0]
        name_var.set(values[1])
        email_var.set(values[2])
        age_var.set(values[3])
        gender_var.set(values[4])
        city_var.set(values[5])

def validate_inputs():
    if not name_var.get().strip():
        messagebox.showwarning("Input Error", "Name cannot be empty.")
        return False
    if not email_var.get().strip():
        messagebox.showwarning("Input Error", "Email cannot be empty.")
        return False
    if not age_var.get().isdigit() or int(age_var.get()) <= 0:
        messagebox.showwarning("Input Error", "Age must be a positive number.")
        return False
    if gender_var.get() not in ["Male", "Female", "Other"]:
        messagebox.showwarning("Input Error", "Please select a valid gender.")
        return False
    if not city_var.get().strip():
        messagebox.showwarning("Input Error", "City cannot be empty.")
        return False
    return True

root = Tk()
root.title("User Management - Improved")
root.geometry("700x500")

connect_db()

current_user_id = None

name_var = StringVar()
email_var = StringVar()
age_var = StringVar()
gender_var = StringVar(value="Male")
city_var = StringVar()

# Form Labels and Entries
Label(root, text="Name").place(x=30, y=30)
Entry(root, textvariable=name_var, width=30).place(x=120, y=30)

Label(root, text="Email").place(x=30, y=70)
Entry(root, textvariable=email_var, width=30).place(x=120, y=70)

Label(root, text="Age").place(x=30, y=110)
Entry(root, textvariable=age_var, width=30).place(x=120, y=110)

Label(root, text="Gender").place(x=30, y=150)
gender_combo = ttk.Combobox(root, textvariable=gender_var, values=["Male", "Female", "Other"], state="readonly", width=28)
gender_combo.place(x=120, y=150)

Label(root, text="City").place(x=30, y=190)
Entry(root, textvariable=city_var, width=30).place(x=120, y=190)

btn_width = 15
btn_height = 1

# Buttons (fetch by email button removed)
Button(root, text="Add", width=btn_width, height=btn_height, command=add_user).place(x=30, y=230)
Button(root, text="Apply Update", width=btn_width, height=btn_height, command=update_user).place(x=180, y=230)
Button(root, text="Delete", width=btn_width, height=btn_height, command=delete_user).place(x=30, y=270)
Button(root, text="Clear", width=btn_width, height=btn_height, command=clear_fields).place(x=180, y=270)

# Treeview (Grid) for displaying users
columns = ("ID", "Name", "Email", "Age", "Gender", "City")
tree = ttk.Treeview(root, columns=columns, show="headings", height=10)
tree.place(x=30, y=320, width=640)

for col in columns:
    tree.heading(col, text=col)
    tree.column(col, width=100)

tree.bind("<<TreeviewSelect>>", on_tree_select)

# Load users into Treeview at startup
load_users()

root.mainloop()
