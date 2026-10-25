//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_plan_contents.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportPlanContents {
  /// Returns a new [SupportPlanContents] instance.
  SupportPlanContents({
    required this.siasStage,

    required this.needs,

    required this.accommodations,
  });

  @JsonKey(name: r'sias_stage', required: true, includeIfNull: false)
  final String siasStage;

  @JsonKey(name: r'needs', required: true, includeIfNull: false)
  final String needs;

  @JsonKey(name: r'accommodations', required: true, includeIfNull: false)
  final String accommodations;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportPlanContents &&
          other.siasStage == siasStage &&
          other.needs == needs &&
          other.accommodations == accommodations;

  @override
  int get hashCode =>
      siasStage.hashCode + needs.hashCode + accommodations.hashCode;

  factory SupportPlanContents.fromJson(Map<String, dynamic> json) =>
      _$SupportPlanContentsFromJson(json);

  Map<String, dynamic> toJson() => _$SupportPlanContentsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
