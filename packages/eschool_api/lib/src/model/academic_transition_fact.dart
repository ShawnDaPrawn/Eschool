//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_fact.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionFact {
  /// Returns a new [AcademicTransitionFact] instance.
  AcademicTransitionFact({
    required this.id,

    required this.kind,

    required this.effectiveOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'effective_on', required: true, includeIfNull: false)
  final String effectiveOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionFact &&
          other.id == id &&
          other.kind == kind &&
          other.effectiveOn == effectiveOn;

  @override
  int get hashCode => id.hashCode + kind.hashCode + effectiveOn.hashCode;

  factory AcademicTransitionFact.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionFactFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionFactToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
