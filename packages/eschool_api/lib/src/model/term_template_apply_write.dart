//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'term_template_apply_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TermTemplateApplyWrite {
  /// Returns a new [TermTemplateApplyWrite] instance.
  TermTemplateApplyWrite({required this.sessionYearId, this.apply = false});

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(
    defaultValue: false,
    name: r'apply',
    required: false,
    includeIfNull: false,
  )
  final bool? apply;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TermTemplateApplyWrite &&
          other.sessionYearId == sessionYearId &&
          other.apply == apply;

  @override
  int get hashCode => sessionYearId.hashCode + apply.hashCode;

  factory TermTemplateApplyWrite.fromJson(Map<String, dynamic> json) =>
      _$TermTemplateApplyWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TermTemplateApplyWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
