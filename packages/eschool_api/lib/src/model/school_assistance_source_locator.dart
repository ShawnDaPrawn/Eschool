//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_source_locator.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceSourceLocator {
  /// Returns a new [SchoolAssistanceSourceLocator] instance.
  SchoolAssistanceSourceLocator({this.id, this.year, this.termId, this.onDate});

  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final String? id;

  // minimum: 1
  @JsonKey(name: r'year', required: false, includeIfNull: false)
  final int? year;

  // minimum: 1
  @JsonKey(name: r'term_id', required: false, includeIfNull: false)
  final int? termId;

  @JsonKey(name: r'on_date', required: false, includeIfNull: false)
  final String? onDate;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceSourceLocator &&
          other.id == id &&
          other.year == year &&
          other.termId == termId &&
          other.onDate == onDate;

  @override
  int get hashCode =>
      id.hashCode +
      year.hashCode +
      (termId == null ? 0 : termId.hashCode) +
      onDate.hashCode;

  factory SchoolAssistanceSourceLocator.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceSourceLocatorFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceSourceLocatorToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
