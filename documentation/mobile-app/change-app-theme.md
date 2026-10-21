---
sidebar_position: 9
---

# Change App Theme

Both apps take their colours from one shared file:

```
packages/eschool_core/lib/src/theme/brand_colors.dart
```

Set `primary` to your main brand colour and `accent` to your second colour. The apps build every other colour (text, backgrounds, borders) from `primary`, so you don't set those by hand. `success`, `warning`, `danger` and `info` are the colours for statuses such as present, pending and absent.

After changing the file, rebuild both apps.