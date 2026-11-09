//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_book_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_shelf.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingShelf {
  /// Returns a new [ReadingShelf] instance.
  ReadingShelf({
    required this.id,

    required this.name,

    required this.description,

    required this.books,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(name: r'books', required: true, includeIfNull: false)
  final List<ReadingBookCard> books;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingShelf &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.books == books;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      books.hashCode;

  factory ReadingShelf.fromJson(Map<String, dynamic> json) =>
      _$ReadingShelfFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingShelfToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
