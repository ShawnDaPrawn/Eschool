import 'dart:io';

import 'package:eschool/utils/tutorCameraCleanupIo.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:path_provider_platform_interface/path_provider_platform_interface.dart';

class _Paths extends PathProviderPlatform {
  _Paths(this.path);
  final String path;
  @override
  Future<String?> getTemporaryPath() async => path;
}

void main() {
  test('camera cleanup removes only an actual app-temporary capture', () async {
    final root = await Directory.systemTemp.createTemp('tutor-camera-owned-');
    final temporary = await Directory('${root.path}/app-cache').create();
    final original = PathProviderPlatform.instance;
    PathProviderPlatform.instance = _Paths(temporary.path);
    addTearDown(() async {
      PathProviderPlatform.instance = original;
      await root.delete(recursive: true);
    });
    final capture = await File('${temporary.path}/capture.png')
        .writeAsString('synthetic temporary capture');
    await clearTutorCameraCopy(capture.path);
    expect(await capture.exists(), isFalse);
    final gallery = await File('${root.path}/gallery-original.png')
        .writeAsString('synthetic device original');
    await clearTutorCameraCopy('${temporary.path}/../gallery-original.png');
    expect(await gallery.readAsString(), 'synthetic device original');
    final link = await Link('${temporary.path}/capture-link.png')
        .create(gallery.path);
    await clearTutorCameraCopy(link.path);
    expect(await gallery.readAsString(), 'synthetic device original');
    expect(await link.exists(), isTrue);
  });
}
