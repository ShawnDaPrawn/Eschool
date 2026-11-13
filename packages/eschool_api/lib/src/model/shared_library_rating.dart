//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_rating.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryRating {
  /// Returns a new [SharedLibraryRating] instance.
  SharedLibraryRating({
    required this.stars,

    required this.comment,

    required this.counted,
  });

  // minimum: 1
  // maximum: 5
  @JsonKey(name: r'stars', required: true, includeIfNull: false)
  final int stars;

  @JsonKey(name: r'comment', required: true, includeIfNull: true)
  final String? comment;

  @JsonKey(name: r'counted', required: true, includeIfNull: false)
  final bool counted;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryRating &&
          other.stars == stars &&
          other.comment == comment &&
          other.counted == counted;

  @override
  int get hashCode =>
      stars.hashCode +
      (comment == null ? 0 : comment.hashCode) +
      counted.hashCode;

  factory SharedLibraryRating.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryRatingFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryRatingToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
