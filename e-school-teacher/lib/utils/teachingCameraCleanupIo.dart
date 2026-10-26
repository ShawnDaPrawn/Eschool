import 'dart:io';

import 'package:path_provider/path_provider.dart';

/// Delete only an app-temporary capture, never a device gallery original.
Future<void> clearTeachingCameraCopy(String path) async {
  try {
    final temporary = await getTemporaryDirectory();
    final file = File(path);
    if (!await file.exists()) return;
    final root =
        '${await temporary.resolveSymbolicLinks()}${Platform.pathSeparator}';
    final resolved = await file.resolveSymbolicLinks();
    if (resolved.startsWith(root)) await File(resolved).delete();
  } catch (_) {
    /* Platform cache expiry remains the fallback. */
  }
}
