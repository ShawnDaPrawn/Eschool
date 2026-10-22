import 'dart:typed_data';

import 'package:flutter/widgets.dart';
import 'package:pdfx/pdfx.dart';

Widget buildPdfDocumentView({required String filePath, Uint8List? bytes}) =>
    _BrowserPdfView(bytes: bytes ?? Uint8List(0));

class _BrowserPdfView extends StatefulWidget {
  const _BrowserPdfView({required this.bytes});

  final Uint8List bytes;

  @override
  State<_BrowserPdfView> createState() => _BrowserPdfViewState();
}

class _BrowserPdfViewState extends State<_BrowserPdfView> {
  late final PdfControllerPinch _controller = PdfControllerPinch(
    document: PdfDocument.openData(widget.bytes),
  );

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => PdfViewPinch(controller: _controller);
}
