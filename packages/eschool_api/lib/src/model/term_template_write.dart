//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'term_template_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermTemplateWrite {
  /// Returns a new [TermTemplateWrite] instance.
  TermTemplateWrite({required this.sessionYearId, required this.name});

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermTemplateWrite &&
          other.sessionYearId == sessionYearId &&
          other.name == name;

  @override
  int get hashCode => sessionYearId.hashCode + name.hashCode;

  factory TermTemplateWrite.fromJson(Map<String, dynamic> json) =>
      _$TermTemplateWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TermTemplateWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
