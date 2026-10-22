import 'package:eschool_core/src/models/study_material.dart';

/// What the image viewer route (`/imageFileView`) shows, in both apps.
class ImageFileViewArguments {
  const ImageFileViewArguments({
    required this.studyMaterial,
    this.multiStudyMaterial = const [],
    this.isFile = false,
    this.initialPage,
    this.title,
  });

  /// The image to show first.
  final StudyMaterial studyMaterial;

  /// All the images to page through; empty for a single image.
  final List<StudyMaterial> multiStudyMaterial;

  /// Whether [studyMaterial] is a file on the device rather than a URL.
  final bool isFile;
  final int? initialPage;
  final String? title;
}

/// What the PDF viewer route (`/pdfFileView`) shows, in both apps.
class PdfFileViewArguments {
  const PdfFileViewArguments({
    required this.studyMaterial,
    this.isFile = false,
  });

  final StudyMaterial studyMaterial;

  /// Whether [studyMaterial] is a file on the device rather than a URL.
  final bool isFile;
}
