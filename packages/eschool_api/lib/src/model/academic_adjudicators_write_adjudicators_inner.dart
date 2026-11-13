//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_adjudicators_write_adjudicators_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAdjudicatorsWriteAdjudicatorsInner {
  /// Returns a new [AcademicAdjudicatorsWriteAdjudicatorsInner] instance.
  AcademicAdjudicatorsWriteAdjudicatorsInner({this.userId, this.name});

  // minimum: 1
  @JsonKey(name: r'user_id', required: false, includeIfNull: false)
  final int? userId;

  @JsonKey(name: r'name', required: false, includeIfNull: false)
  final String? name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAdjudicatorsWriteAdjudicatorsInner &&
          other.userId == userId &&
          other.name == name;

  @override
  int get hashCode =>
      (userId == null ? 0 : userId.hashCode) +
      (name == null ? 0 : name.hashCode);

  factory AcademicAdjudicatorsWriteAdjudicatorsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicAdjudicatorsWriteAdjudicatorsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicAdjudicatorsWriteAdjudicatorsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
