import 'package:eschool_core/src/models/event_schedule.dart';
import 'package:eschool_core/src/models/gallery_album.dart';

// What the shared cubits read from the server. Each app's SystemRepository
// implements these against its own Api, and tests pass fakes.

abstract interface class AppSettingsRepository {
  /// The `data` of `GET settings?type=<type>`: the about, contact, privacy
  /// and terms pages, or the app settings.
  Future<dynamic> fetchSettings({required String type});
}

abstract interface class EventScheduleRepository {
  Future<List<EventSchedule>> fetchEventDetails({required String eventId});
}

abstract interface class GalleryRepository {
  Future<({List<GalleryAlbum> albums, int currentPage, int totalPages})>
  fetchGalleryAlbums({required int page});

  Future<List<String>> fetchGalleryAlbumPhotos({required String albumId});

  Future<({List<String> videos, int currentPage, int totalPages})>
  fetchGalleryVideos({required int page});
}
