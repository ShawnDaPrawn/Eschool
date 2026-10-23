import 'dart:typed_data';

import 'package:flutter/widgets.dart';

import 'pdf_document_view_io.dart'
    if (dart.library.js_interop) 'pdf_document_view_web.dart'
    as platform;

/// A PDF on screen. A phone shows [bytes] or the downloaded [filePath] with the
/// platform's own PDF view (flutter_pdfview), as the apps always have. A
/// browser has no files, so it shows the downloaded [bytes] with pdf.js
/// (pdfx), which each app's `web/index.html` loads.
class PdfDocumentView extends StatelessWidget {
  const PdfDocumentView({
    this.filePath = '',
    this.bytes,
    this.onLoaded,
    this.onError,
    super.key,
  });

  final String filePath;
  final Uint8List? bytes;
  final VoidCallback? onLoaded;
  final VoidCallback? onError;

  @override
  Widget build(BuildContext context) => platform.buildPdfDocumentView(
    filePath: filePath,
    bytes: bytes,
    onLoaded: onLoaded,
    onError: onError,
  );
}
