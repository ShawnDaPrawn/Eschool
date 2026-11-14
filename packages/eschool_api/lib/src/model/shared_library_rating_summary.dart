//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_rating_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryRatingSummary {
  /// Returns a new [SharedLibraryRatingSummary] instance.
  SharedLibraryRatingSummary({required this.count, required this.average});

  // minimum: 1
  @JsonKey(name: r'count', required: true, includeIfNull: false)
  final int count;

  // minimum: 1
  // maximum: 5
  @JsonKey(name: r'average', required: true, includeIfNull: false)
  final num average;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryRatingSummary &&
          other.count == count &&
          other.average == average;

  @override
  int get hashCode => count.hashCode + average.hashCode;

  factory SharedLibraryRatingSummary.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryRatingSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryRatingSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
