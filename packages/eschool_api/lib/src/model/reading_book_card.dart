//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_progress.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_book_card.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingBookCard {
  /// Returns a new [ReadingBookCard] instance.
  ReadingBookCard({
    required this.id,

    required this.title,

    required this.author,

    required this.language,

    required this.subjects,

    required this.readingLevel,

    required this.readingLevelLabel,

    required this.pageCount,

    required this.progress,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'author', required: true, includeIfNull: false)
  final String author;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<String> subjects;

  @JsonKey(
    name: r'reading_level',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReadingBookCardReadingLevelEnum.unknownDefaultOpenApi,
  )
  final ReadingBookCardReadingLevelEnum readingLevel;

  @JsonKey(name: r'reading_level_label', required: true, includeIfNull: false)
  final String readingLevelLabel;

  /// Known once the text has been fetched.
  @JsonKey(name: r'page_count', required: true, includeIfNull: true)
  final int? pageCount;

  @JsonKey(name: r'progress', required: true, includeIfNull: true)
  final ReadingProgress? progress;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingBookCard &&
          other.id == id &&
          other.title == title &&
          other.author == author &&
          other.language == language &&
          other.subjects == subjects &&
          other.readingLevel == readingLevel &&
          other.readingLevelLabel == readingLevelLabel &&
          other.pageCount == pageCount &&
          other.progress == progress;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      author.hashCode +
      language.hashCode +
      subjects.hashCode +
      readingLevel.hashCode +
      readingLevelLabel.hashCode +
      (pageCount == null ? 0 : pageCount.hashCode) +
      (progress == null ? 0 : progress.hashCode);

  factory ReadingBookCard.fromJson(Map<String, dynamic> json) =>
      _$ReadingBookCardFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingBookCardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReadingBookCardReadingLevelEnum {
  @JsonValue(r'beginner')
  beginner(r'beginner'),
  @JsonValue(r'developing')
  developing(r'developing'),
  @JsonValue(r'confident')
  confident(r'confident'),
  @JsonValue(r'fluent')
  fluent(r'fluent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReadingBookCardReadingLevelEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
