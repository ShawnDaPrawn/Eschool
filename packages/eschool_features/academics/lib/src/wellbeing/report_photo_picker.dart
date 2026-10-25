import 'dart:typed_data';
import 'package:eschool_core/eschool_core.dart';
import 'package:file_picker/file_picker.dart';

/// Keeps a bounded memory copy; never retains the picked filename or path.
/// Platform picker/cache behavior still needs physical-device qualification.
Future<Uint8List?> selectReportPhoto() async {
  final file = await FilePicker.pickFile(
    type: FileType.custom,
    allowedExtensions: ['jpg', 'jpeg', 'png'],
  );
  if (file == null) return null;
  try {
    const maximum = 4194304;
    final size = await file.length();
    if (size == null || size < 1 || size > maximum) {
      throw ApiException(
        'Choose a JPEG or PNG photo up to 4 MB.',
        isApiMessage: true,
      );
    }
    final bytes = BytesBuilder(copy: true);
    await for (final chunk in file.readAsByteStream()) {
      if (bytes.length + chunk.length > maximum) {
        throw ApiException(
          'Choose a JPEG or PNG photo up to 4 MB.',
          isApiMessage: true,
        );
      }
      bytes.add(chunk);
    }
    if (bytes.length != size)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return bytes.takeBytes();
  } finally {
    // Supported native pickers may create transient copies. Ask the plugin to
    // release them; unsupported platforms must not turn a safe read into a send.
    try {
      await FilePicker.clearTemporaryFiles();
    } catch (_) {}
  }
}
