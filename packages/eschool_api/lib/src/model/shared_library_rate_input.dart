//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_rate_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryRateInput {
  /// Returns a new [SharedLibraryRateInput] instance.
  SharedLibraryRateInput({required this.stars, this.comment});

  // minimum: 1
  // maximum: 5
  @JsonKey(name: r'stars', required: true, includeIfNull: false)
  final int stars;

  @JsonKey(name: r'comment', required: false, includeIfNull: false)
  final String? comment;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryRateInput &&
          other.stars == stars &&
          other.comment == comment;

  @override
  int get hashCode => stars.hashCode + (comment == null ? 0 : comment.hashCode);

  factory SharedLibraryRateInput.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryRateInputFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryRateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
