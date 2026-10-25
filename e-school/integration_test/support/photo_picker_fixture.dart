import 'dart:convert';
import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';

final class SyntheticPhotoFile extends PlatformFile {
  static const pixels =
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNQcGj4DwADRAHgMqq/hAAAAABJRU5ErkJggg==';
  @override
  String get name => 'synthetic-device-location.png';
  @override
  Uri get uri => Uri.parse('blob:synthetic-photo-fixture');
  @override
  Never get xFile => throw StateError('Only bounded streaming is expected.');
  @override
  int? lengthSync() => base64Decode(pixels).length;
  @override
  Future<int?> length() async => lengthSync();
  @override
  Future<Uint8List> readAsBytes() =>
      throw StateError('Read the bounded stream.');
  @override
  Stream<Uint8List> readAsByteStream() async* {
    yield base64Decode(pixels);
  }
}

class SyntheticPhotoPicker extends FilePickerPlatform {
  @override
  Future<PlatformFile?> pickFile({
    String? dialogTitle,
    String? initialDirectory,
    FileType type = FileType.any,
    List<String>? allowedExtensions,
    Function(FilePickerStatus)? onFileLoading,
    int compressionQuality = 0,
    AndroidOptions androidOptions = const AndroidOptions(),
    DarwinOptions darwinOptions = const DarwinOptions(),
    WindowsOptions windowsOptions = const WindowsOptions(),
    LinuxOptions linuxOptions = const LinuxOptions(),
    WebOptions webOptions = const WebOptions(),
  }) async => SyntheticPhotoFile();
  @override
  Future<void> clearTemporaryFiles() async {}
}
