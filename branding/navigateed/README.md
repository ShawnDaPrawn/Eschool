# NavigateEd book-and-scroll brand integration

This is the new default **NavigateEd education identity**, matching Navigate Wealth's navy/purple family while replacing the sailboat with an academic book and scroll.

## Placement and contrast
- Site header, navigation drawer, school admin header/login: navy/purple **primary** SVG on light surfaces, **transparent** background.
- Site footer: navy/purple **primary** SVG because its current design token is a light surface. Use the white/purple **reversed** SVG only if a surface is actually dark. Both wordmarks are transparent.
- Compact admin navbar: transparent **icon** on light surfaces.
- Student/parent and teacher in-app logos: navy/purple book/scroll SVG, transparent background.
- Student/parent phone launcher: book/scroll in white/purple on an fully opaque deep-violet background (no corner transparency; the operating system rounds launcher icons).
- Teacher launcher: same mark, slightly different violet background, to be recognisable.
- Favicon: opaque square app icon.

Website code continues to prefer school-specific uploads in `schoolLogoUrl()`, including the favicon. Branded default assets apply only when no school logo has been uploaded.

## Native mobile icon workflow
Run `python -m pip install cairosvg pillow` and `python scripts/generate_navigateed_icons.py` to render all Android densities, the adaptive foreground, iOS iconset sizes, and Flutter launcher PNGs. A GitHub Action on this review branch generates and commits these assets automatically; do not merge until it finishes. Shipping on devices still requires distributing a new app build.

## QA
Check public header/footer in light and dark schemes, verify that transparent SVGs do not render as blocks, check uploaded school logos remain untouched, and build/run both Flutter apps on real devices to inspect launchers and splash screens.
