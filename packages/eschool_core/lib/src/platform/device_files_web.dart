import 'dart:async';
import 'dart:js_interop';
import 'dart:typed_data';

import 'package:eschool_core/src/platform/device_files.dart';
import 'package:web/web.dart' as web;

Future<String> saveDocument(List<int> bytes, String relativePath) async {
  final web.Blob blob = web.Blob(
    <JSAny>[Uint8List.fromList(bytes).toJS].toJS,
    web.BlobPropertyBag(type: _mimeTypeOf(relativePath)),
  );
  final String url = web.URL.createObjectURL(blob);
  final web.HTMLAnchorElement anchor =
      web.document.createElement('a') as web.HTMLAnchorElement
        ..href = url
        ..download = DeviceFiles.fileNameOf(relativePath)
        ..style.display = 'none';
  web.document.body?.append(anchor);
  anchor.click();
  anchor.remove();
  // Give the browser time to start the download before the URL goes.
  Timer(const Duration(minutes: 1), () => web.URL.revokeObjectURL(url));
  return '';
}

Future<bool> open(String pathOrUrl) async {
  if (pathOrUrl.isEmpty) {
    return true;
  }
  final Uri? uri = Uri.tryParse(pathOrUrl);
  if (uri == null || !(uri.isScheme('https') || uri.isScheme('http'))) {
    return false;
  }
  web.window.open(uri.toString(), '_blank', 'noopener');
  return true;
}

String _mimeTypeOf(String path) {
  final String name = path.toLowerCase();
  if (name.endsWith('.pdf')) {
    return 'application/pdf';
  }
  if (name.endsWith('.png')) {
    return 'image/png';
  }
  if (name.endsWith('.jpg') || name.endsWith('.jpeg')) {
    return 'image/jpeg';
  }
  return 'application/octet-stream';
}
