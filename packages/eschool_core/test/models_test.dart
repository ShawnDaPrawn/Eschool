import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('Gallery', () {
    test('reads albums and videos', () {
      final gallery = Gallery.fromJson({
        'albums': [
          {'id': 3, 'title': 'Sports day', 'thumbnail_url': 'https://x/t.jpg'},
        ],
        'videos': ['https://x/v.mp4'],
      });

      expect(gallery.albums!.single.id, '3');
      expect(gallery.albums!.single.title, 'Sports day');
      expect(gallery.albums!.single.thumbnail, 'https://x/t.jpg');
      expect(gallery.videos, ['https://x/v.mp4']);
      expect(gallery.hasData(), isTrue);
    });

    test('has no data when both lists are missing or empty', () {
      expect(Gallery.fromJson({}).hasData(), isFalse);
      expect(Gallery.fromJson({'albums': [], 'videos': []}).hasData(), isFalse);
    });
  });

  test('GalleryAlbum falls back to empty strings', () {
    final album = GalleryAlbum.fromJson({});

    expect(album.id, '');
    expect(album.title, '');
    expect(album.thumbnail, '');
  });

  group('ChatSettings', () {
    test('reads the limits the server sends', () {
      final settings = ChatSettings.fromJson({
        'max_files_or_images_in_one_message': '4',
        'max_file_size_in_bytes': 2048,
        'max_characters_in_text_message': '120',
        'automatically_messages_removed_days': 30,
      });

      expect(settings.maxFilesOrImagesInOneMessage, 4);
      expect(settings.maxFileSizeInBytesCanBeSent, 2048);
      expect(settings.maxCharactersInATextMessage, 120);
      expect(settings.automaticallyMessagesRemovedDays, 30);
    });

    test('uses defaults for missing or invalid values', () {
      final settings = ChatSettings.fromJson({
        'max_files_or_images_in_one_message': 'many',
      });

      expect(settings.maxFilesOrImagesInOneMessage, 10);
      expect(settings.maxFileSizeInBytesCanBeSent, 10000000);
      expect(settings.maxCharactersInATextMessage, 500);
      expect(settings.automaticallyMessagesRemovedDays, 0);
    });
  });

  group('EventSchedule', () {
    test('round-trips through json with the date as yyyy-mm-dd', () {
      final schedule = EventSchedule.fromJson({
        'id': 1,
        'event_id': 2,
        'title': 'Assembly',
        'date': '2026-03-09',
        'start_time': '09:00:00',
        'end_time': null,
        'description': 'Main hall',
      });

      expect(schedule.date, DateTime.parse('2026-03-09'));
      expect(schedule.endTime, isNull);
      expect(schedule.toJson()['date'], '2026-03-09');
      expect(schedule.toJson()['event_id'], 2);
    });
  });

  test('SemesterBreak reads its start and end', () {
    final semesterBreak = SemesterBreak.fromJson({
      'start': '2026-04-01',
      'end': '2026-04-12',
    });

    expect(semesterBreak.startDate, DateTime.parse('2026-04-01'));
    expect(semesterBreak.endDate, DateTime.parse('2026-04-12'));
  });

  group('sanitizeFileName', () {
    test('keeps an ordinary name', () {
      expect(sanitizeFileName('report card.pdf'), 'report card.pdf');
    });

    test('cannot escape the directory', () {
      expect(sanitizeFileName('../../etc/passwd'), isNot(contains('/')));
      expect(sanitizeFileName('..\\..\\x'), isNot(contains('\\')));
      expect(sanitizeFileName('a/../b'), isNot(contains('..')));
    });

    test('falls back when nothing usable is left', () {
      expect(sanitizeFileName('...'), 'file');
      expect(sanitizeFileName(' . ', fallback: 'unnamed'), 'unnamed');
    });
  });
}
