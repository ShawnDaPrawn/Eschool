import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeGalleryRepository implements GalleryRepository {
  final List<int> albumPagesAsked = [];
  final List<String> albumIdsAsked = [];
  bool fail = false;

  @override
  Future<({List<GalleryAlbum> albums, int currentPage, int totalPages})>
  fetchGalleryAlbums({required int page}) async {
    albumPagesAsked.add(page);
    if (fail) throw ApiException('302');
    return (
      albums: [
        GalleryAlbum.fromJson({'id': page, 'title': 'Album $page'}),
      ],
      currentPage: page,
      totalPages: 2,
    );
  }

  @override
  Future<List<String>> fetchGalleryAlbumPhotos({
    required String albumId,
  }) async {
    albumIdsAsked.add(albumId);
    return ['https://school.test/$albumId/1.jpg'];
  }

  @override
  Future<({List<String> videos, int currentPage, int totalPages})>
  fetchGalleryVideos({required int page}) async {
    if (fail) throw ApiException('302');
    return (
      videos: ['https://youtu.be/v$page'],
      currentPage: page,
      totalPages: 1,
    );
  }
}

class FakeSchoolInfoRepository
    implements AppSettingsRepository, EventScheduleRepository {
  dynamic settings;
  bool fail = false;

  @override
  Future<dynamic> fetchSettings({required String type}) async {
    if (fail) throw ApiException('500');
    return settings;
  }

  @override
  Future<List<EventSchedule>> fetchEventDetails({
    required String eventId,
  }) async {
    EventSchedule on(String date) => EventSchedule.fromJson({
      'event_id': int.parse(eventId),
      'title': 'Sports day',
      'date': date,
    });
    return [on('2026-05-03'), on('2026-05-01'), on('2026-05-03')];
  }
}

void main() {
  group('GalleryAlbumCubit', () {
    test('loads the first page, then the next one', () async {
      final repository = FakeGalleryRepository();
      final cubit = GalleryAlbumCubit(repository);

      await cubit.fetchAlbums();
      expect(cubit.state, isA<GalleryAlbumFetchSuccess>());
      expect(cubit.hasMore(), isTrue);

      await cubit.fetchMoreAlbums();
      final state = cubit.state as GalleryAlbumFetchSuccess;
      expect(state.albums.map((album) => album.title), ['Album 1', 'Album 2']);
      expect(repository.albumPagesAsked, [1, 2]);
      expect(cubit.hasMore(), isFalse);
    });

    test('says why the albums could not load', () async {
      final cubit = GalleryAlbumCubit(FakeGalleryRepository()..fail = true);

      await cubit.fetchAlbums();
      expect(cubit.state, isA<GalleryAlbumFetchFailure>());
      expect((cubit.state as GalleryAlbumFetchFailure).errorMessage, '302');
    });
  });

  test('GalleryVideosCubit loads the videos', () async {
    final cubit = GalleryVideosCubit(FakeGalleryRepository());

    await cubit.fetchVideos();
    expect((cubit.state as GalleryVideosFetchSuccess).videos, [
      'https://youtu.be/v1',
    ]);
  });

  test('GalleryAlbumPhotosCubit asks for the album it was given', () async {
    final repository = FakeGalleryRepository();
    final album = GalleryAlbum.fromJson({'id': 7, 'title': 'Prize giving'});
    final cubit = GalleryAlbumPhotosCubit(
      album: album,
      galleryRepository: repository,
    );

    await cubit.fetchAlbumPhotos();
    expect(repository.albumIdsAsked, ['7']);
    expect((cubit.state as GalleryAlbumPhotosFetchSuccess).photos, [
      'https://school.test/7/1.jpg',
    ]);
  });

  test('EventDetailsCubit lists each day once, in order', () async {
    final cubit = EventDetailsCubit(FakeSchoolInfoRepository());

    await cubit.fetchEventDetails(eventId: '4');
    final state = cubit.state as EventDetailsFetchSuccess;
    expect(state.eventDetails, hasLength(3));
    expect(state.sortedDates, [DateTime(2026, 5, 1), DateTime(2026, 5, 3)]);
  });

  group('AppSettingsCubit', () {
    test('keeps the page the server sent, or an empty one', () async {
      final repository = FakeSchoolInfoRepository()..settings = '<p>Hi</p>';
      final cubit = AppSettingsCubit(repository);

      await cubit.fetchAppSettings(type: 'about_us');
      expect(
        (cubit.state as AppSettingsFetchSuccess).appSettingsResult,
        '<p>Hi</p>',
      );

      repository.settings = null;
      await cubit.fetchAppSettings(type: 'about_us');
      expect((cubit.state as AppSettingsFetchSuccess).appSettingsResult, '');
    });

    test('fails with the error code', () async {
      final cubit = AppSettingsCubit(FakeSchoolInfoRepository()..fail = true);

      await cubit.fetchAppSettings(type: 'about_us');
      expect((cubit.state as AppSettingsFetchFailure).errorMessage, '500');
    });
  });
}
