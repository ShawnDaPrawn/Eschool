import 'package:flutter/widgets.dart';

import 'local_image_io.dart'
    if (dart.library.js_interop) 'local_image_web.dart'
    as platform;

/// An image the user picked on this device. On a phone [path] is a file; in
/// a browser it is the `blob:` address the picker gives instead.
Widget localImage(String path, {BoxFit fit = BoxFit.cover}) =>
    platform.localImage(path, fit: fit);
