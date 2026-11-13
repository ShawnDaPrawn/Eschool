//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_adjudicators_write_adjudicators_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_adjudicators_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAdjudicatorsWrite {
  /// Returns a new [AcademicAdjudicatorsWrite] instance.
  AcademicAdjudicatorsWrite({
    required this.operationId,

    required this.adjudicators,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  /// Replaces the list. Each is one of the school's staff (user_id) or someone from outside (name).
  @JsonKey(name: r'adjudicators', required: true, includeIfNull: false)
  final List<AcademicAdjudicatorsWriteAdjudicatorsInner> adjudicators;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAdjudicatorsWrite &&
          other.operationId == operationId &&
          other.adjudicators == adjudicators;

  @override
  int get hashCode => operationId.hashCode + adjudicators.hashCode;

  factory AcademicAdjudicatorsWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicAdjudicatorsWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAdjudicatorsWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
