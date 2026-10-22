#!/usr/bin/env python3
"""Update launcher PNGs from NavigateEd SVG masters (not school-uploaded logos)."""
import io, re
from pathlib import Path
import cairosvg
from PIL import Image

ROOT=Path(__file__).resolve().parents[1]
BRAND=ROOT/"branding"/"navigateed"
SITE=ROOT/"admin-panel"/"public"

def write(src:Path,size:int,dest:Path):
    b=cairosvg.svg2png(url=str(src),output_width=size,output_height=size)
    img=Image.open(io.BytesIO(b)).convert("RGBA")
    dest.parent.mkdir(parents=True,exist_ok=True)
    img.save(dest,"PNG",optimize=True)

for name,variant in (("e-school","student"),("e-school-teacher","teacher")):
    root=ROOT/name
    asset=root/"assets"/"images"/"branding"
    launcher=BRAND/f"{variant}-app-icon.svg"
    foreground=BRAND/"adaptive-foreground.svg"
    write(launcher,1024,asset/"ic_launcher.png")
    write(foreground,1024,asset/"ic_launcher_transparent.png")
    android=root/"android"/"app"/"src"/"main"/"res"
    for scale,size in {"mdpi":48,"hdpi":72,"xhdpi":96,"xxhdpi":144,"xxxhdpi":192}.items():
        write(launcher,size,android/f"mipmap-{scale}"/"ic_launcher.png")
    for scale,size in {"mdpi":108,"hdpi":162,"xhdpi":216,"xxhdpi":324,"xxxhdpi":432}.items():
        write(foreground,size,android/f"drawable-{scale}"/"ic_launcher_foreground.png")
    ios=root/"ios"/"Runner"/"Assets.xcassets"/"AppIcon.appiconset"
    for p in ios.glob("Icon-App-*.png"):
        m=re.match(r"Icon-App-(\d+(?:\.\d+)?)x\d+(?:\.\d+)?@(\d+)x\.png",p.name)
        if m:
            write(launcher,round(float(m.group(1))*int(m.group(2))),p)
fav=BRAND/"student-app-icon.svg"
for n in (16,32,48,64,180,192,512):
    write(fav,n,SITE/"assets"/"navigateed"/f"favicon-{n}.png")
write(fav,180,SITE/"apple-touch-icon.png")
