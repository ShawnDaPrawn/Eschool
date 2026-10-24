//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'term_template.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermTemplate {
  /// Returns a new [TermTemplate] instance.
  TermTemplate({
    required this.id,

    required this.name,

    required this.baseYear,

    required this.termCount,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'base_year', required: true, includeIfNull: false)
  final int baseYear;

  @JsonKey(name: r'term_count', required: true, includeIfNull: false)
  final int termCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermTemplate &&
          other.id == id &&
          other.name == name &&
          other.baseYear == baseYear &&
          other.termCount == termCount;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + baseYear.hashCode + termCount.hashCode;

  factory TermTemplate.fromJson(Map<String, dynamic> json) =>
      _$TermTemplateFromJson(json);

  Map<String, dynamic> toJson() => _$TermTemplateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
