---
sidebar_position: 9
---

# Change App Theme

Both apps take their colours from one shared file:

```
packages/eschool_core/lib/src/theme/brand_colors.dart
```

The file holds the colours of the Navigate Wealth design system:

- `primary`: buttons, links and selected items.
- `darkNavy`: the header at the top of every screen.
- `accent`: highlights such as event times.
- `background`, `lightGray`, `text`, `mutedText` and `border`: page and card backgrounds, input fills, text, and dividers.
- `success`, `warning`, `danger` and `info`: statuses such as present, pending and absent.

The apps work out the remaining shades and the text colour on each background themselves, so you don't set those by hand.

After changing the file, rebuild both apps.