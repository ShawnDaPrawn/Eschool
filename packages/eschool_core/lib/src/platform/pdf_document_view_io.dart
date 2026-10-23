import 'dart:typed_data';

import 'package:flutter/widgets.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';

Widget buildPdfDocumentView({
  required String filePath,
  Uint8List? bytes,
  VoidCallback? onLoaded,
  VoidCallback? onError,
}) => PDFView(
  filePath: filePath.isEmpty ? null : filePath,
  pdfData: bytes,
  onRender: (pages) => (pages ?? 0) > 0 ? onLoaded?.call() : onError?.call(),
  onError: (_) => onError?.call(),
  onPageError: (_, _) => onError?.call(),
  pageFling: false,
);
