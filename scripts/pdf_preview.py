#!/usr/bin/env python3
"""Render the first pages of a PDF to PNG so the layout can be inspected."""
import sys
import pathlib

import fitz  # PyMuPDF

pdf_path = sys.argv[1]
out_dir = pathlib.Path(sys.argv[2] if len(sys.argv) > 2 else "storage/app/browser")
pages = int(sys.argv[3]) if len(sys.argv) > 3 else 3

out_dir.mkdir(parents=True, exist_ok=True)

doc = fitz.open(pdf_path)
print(f"{pdf_path}: {doc.page_count} pages")

stem = pathlib.Path(pdf_path).stem

for index in range(min(pages, doc.page_count)):
    page = doc[index]
    pix = page.get_pixmap(dpi=110)
    target = out_dir / f"pdf-{stem}-{index + 1}.png"
    pix.save(target)
    print(f"page {index + 1}: {pix.width}x{pix.height} -> {target}")
