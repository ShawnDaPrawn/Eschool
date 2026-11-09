//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingPage {
  /// Returns a new [ReadingPage] instance.
  ReadingPage({
    required this.bookId,

    required this.page,

    required this.pageCount,

    required this.text,
  });

  @JsonKey(name: r'book_id', required: true, includeIfNull: false)
  final int bookId;

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'page_count', required: true, includeIfNull: false)
  final int pageCount;

  /// Plain text; paragraphs are separated by a blank line.
  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingPage &&
          other.bookId == bookId &&
          other.page == page &&
          other.pageCount == pageCount &&
          other.text == text;

  @override
  int get hashCode =>
      bookId.hashCode + page.hashCode + pageCount.hashCode + text.hashCode;

  factory ReadingPage.fromJson(Map<String, dynamic> json) =>
      _$ReadingPageFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
