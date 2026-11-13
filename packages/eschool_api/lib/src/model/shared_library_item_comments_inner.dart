//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item_comments_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItemCommentsInner {
  /// Returns a new [SharedLibraryItemCommentsInner] instance.
  SharedLibraryItemCommentsInner({required this.stars, required this.comment});

  @JsonKey(name: r'stars', required: true, includeIfNull: false)
  final int stars;

  @JsonKey(name: r'comment', required: true, includeIfNull: false)
  final String comment;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItemCommentsInner &&
          other.stars == stars &&
          other.comment == comment;

  @override
  int get hashCode => stars.hashCode + comment.hashCode;

  factory SharedLibraryItemCommentsInner.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemCommentsInnerFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryItemCommentsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
