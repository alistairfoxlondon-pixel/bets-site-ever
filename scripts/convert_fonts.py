#!/usr/bin/env python3
"""Convert the bundled woff2 web fonts into TTF files for the PDF renderer."""
import os
from fontTools.ttLib import TTFont

ROOT = "/home/user/bets-site-ever"
NM = os.path.join(ROOT, "node_modules", "@fontsource")
OUT = os.path.join(ROOT, "resources", "fonts")
os.makedirs(OUT, exist_ok=True)

JOBS = [
    ("tenor-sans/files/tenor-sans-latin-400-normal.woff2", "TenorSans.ttf"),
    ("tenor-sans/files/tenor-sans-latin-ext-400-normal.woff2", "TenorSansExt.ttf"),
    ("source-sans-3/files/source-sans-3-latin-400-normal.woff2", "SourceSans3-Regular.ttf"),
    ("source-sans-3/files/source-sans-3-latin-600-normal.woff2", "SourceSans3-Semibold.ttf"),
    ("source-sans-3/files/source-sans-3-latin-700-normal.woff2", "SourceSans3-Bold.ttf"),
    ("source-sans-3/files/source-sans-3-latin-ext-400-normal.woff2", "SourceSans3-RegularExt.ttf"),
    ("source-sans-3/files/source-sans-3-latin-ext-600-normal.woff2", "SourceSans3-SemiboldExt.ttf"),
    ("source-sans-3/files/source-sans-3-latin-ext-700-normal.woff2", "SourceSans3-BoldExt.ttf"),
    ("ibm-plex-mono/files/ibm-plex-mono-latin-400-normal.woff2", "IBMPlexMono-Regular.ttf"),
    ("ibm-plex-mono/files/ibm-plex-mono-latin-500-normal.woff2", "IBMPlexMono-Medium.ttf"),
]


def main() -> None:
    for rel, name in JOBS:
        src = os.path.join(NM, rel)
        dst = os.path.join(OUT, name)
        if not os.path.exists(src):
            print("missing", src)
            continue
        font = TTFont(src)
        font.flavor = None
        font.save(dst)
        print("wrote", name, os.path.getsize(dst))


if __name__ == "__main__":
    main()
