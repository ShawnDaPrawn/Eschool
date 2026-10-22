/// Names of the routes the shared widgets open. Both apps register a screen
/// under each name, and their `Routes` classes use these values.
// ignore: avoid_classes_with_only_static_members
abstract final class SharedRoutes {
  static const String gallery = '/gallery';
  static const String galleryAlbumPhotos = '/galleryAlbumPhotos';
}
