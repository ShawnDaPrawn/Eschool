import 'dart:typed_data';

import 'package:flutter/widgets.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';

Widget buildPdfDocumentView({required String filePath, Uint8List? bytes}) =>
    PDFView(filePath: filePath, pageFling: false);
