
import os
from pypdf import PdfReader
from docx import Document

# Project's main folder
PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

file_path = input("Enter database file path: ").strip()
full_path = os.path.join(PROJECT_ROOT, file_path)

print("Checking:", full_path)

if not os.path.exists(full_path):
    print("ERROR: File not found!")
else:
    extension = os.path.splitext(full_path)[1].lower()

    if extension == ".pdf":
        reader = PdfReader(full_path)
        text = "\n".join(page.extract_text() or "" for page in reader.pages)

    elif extension == ".docx":
        document = Document(full_path)
        text = "\n".join(p.text for p in document.paragraphs)

    else:
        text = ""
        print("Unsupported file type:", extension)

    if text:
        print("\n--- EXTRACTED TEXT (first 1500 characters) ---")
        print(text[:1500])
    elif extension in [".pdf", ".docx"]:
        print("No readable text found in this file.")
