//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolSummary {
  /// Returns a new [SchoolSummary] instance.
  SchoolSummary({
    required this.id,

    required this.name,

    required this.shortName,

    required this.crestUrl,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'short_name', required: true, includeIfNull: true)
  final String? shortName;

  /// The school's crest or logo, shown beside the NavigateEd brand; null when none is uploaded.
  @JsonKey(name: r'crest_url', required: true, includeIfNull: true)
  final String? crestUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolSummary &&
          other.id == id &&
          other.name == name &&
          other.shortName == shortName &&
          other.crestUrl == crestUrl;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (shortName == null ? 0 : shortName.hashCode) +
      (crestUrl == null ? 0 : crestUrl.hashCode);

  factory SchoolSummary.fromJson(Map<String, dynamic> json) =>
      _$SchoolSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
