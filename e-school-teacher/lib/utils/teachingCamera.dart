import 'dart:typed_data';

import 'package:eschool_core/eschool_core.dart';
import 'package:image_picker/image_picker.dart';

import 'teachingCameraCleanupStub.dart'
    if (dart.library.io) 'teachingCameraCleanupIo.dart';

Future<Uint8List?> selectTeachingCameraPhoto() async {
  final photo = await ImagePicker().pickImage(
    source: ImageSource.camera,
    maxWidth: 768,
    maxHeight: 768,
    imageQuality: 85,
  );
  if (photo == null) return null;
  try {
    const maximum = 4194304;
    final size = await photo.length();
    if (size < 1 || size > maximum)
      throw ApiException(
        'Choose a JPEG or PNG photo up to 4 MB.',
        isApiMessage: true,
      );
    final output = BytesBuilder(copy: true);
    await for (final chunk in photo.openRead()) {
      if (output.length + chunk.length > maximum)
        throw ApiException(
          'Choose a JPEG or PNG photo up to 4 MB.',
          isApiMessage: true,
        );
      output.add(chunk);
    }
    if (output.length != size)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return output.takeBytes();
  } finally {
    await clearTeachingCameraCopy(photo.path);
  }
}
