//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_competition_detail_adjudicators_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCompetitionDetailAdjudicatorsInner {
  /// Returns a new [AcademicCompetitionDetailAdjudicatorsInner] instance.
  AcademicCompetitionDetailAdjudicatorsInner({
    required this.userId,

    required this.name,
  });

  @JsonKey(name: r'user_id', required: true, includeIfNull: true)
  final int? userId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCompetitionDetailAdjudicatorsInner &&
          other.userId == userId &&
          other.name == name;

  @override
  int get hashCode => (userId == null ? 0 : userId.hashCode) + name.hashCode;

  factory AcademicCompetitionDetailAdjudicatorsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicCompetitionDetailAdjudicatorsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicCompetitionDetailAdjudicatorsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
