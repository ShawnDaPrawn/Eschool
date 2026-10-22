import 'device_files_io.dart'
    if (dart.library.js_interop) 'device_files_web.dart'
    as platform;

/// Files the apps save and open, on a phone and in a browser.
///
/// A phone writes them to the app's documents folder and opens them with
/// the app the device uses for their type (open_filex). A browser has no
/// folder for the app: it downloads what a phone would save, and opens web
/// addresses in a new tab, where it shows or downloads them itself.
abstract final class DeviceFiles {
  /// Writes [bytes] to [relativePath] under the app's documents folder,
  /// creating its folders, and returns the full path. In a browser the bytes
  /// become a download named after the file, and the path is empty.
  static Future<String> saveDocument(List<int> bytes, String relativePath) =>
      platform.saveDocument(bytes, relativePath);

  /// Opens a file from [saveDocument], or a file the user picked, with the
  /// app the device uses for its type, and says whether that worked. In a
  /// browser a web address opens in a new tab, and an empty path (a file
  /// that was already downloaded) needs nothing more.
  static Future<bool> open(String pathOrUrl) => platform.open(pathOrUrl);

  /// The file name at the end of [path], for the download a browser makes.
  static String fileNameOf(String path) {
    final List<String> parts = path.split('/');
    return parts.lastWhere((part) => part.isNotEmpty, orElse: () => 'file');
  }
}
