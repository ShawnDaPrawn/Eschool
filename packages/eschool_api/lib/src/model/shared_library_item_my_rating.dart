//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item_my_rating.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItemMyRating {
  /// Returns a new [SharedLibraryItemMyRating] instance.
  SharedLibraryItemMyRating({required this.stars, required this.comment});

  @JsonKey(name: r'stars', required: true, includeIfNull: false)
  final int stars;

  @JsonKey(name: r'comment', required: true, includeIfNull: true)
  final String? comment;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItemMyRating &&
          other.stars == stars &&
          other.comment == comment;

  @override
  int get hashCode => stars.hashCode + (comment == null ? 0 : comment.hashCode);

  factory SharedLibraryItemMyRating.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemMyRatingFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryItemMyRatingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
