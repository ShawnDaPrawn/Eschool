//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_release_period.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_release_periods_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReleasePeriodsData {
  /// Returns a new [AcademicReleasePeriodsData] instance.
  AcademicReleasePeriodsData({required this.learnerId, required this.periods});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'periods', required: true, includeIfNull: false)
  final List<AcademicReleasePeriod> periods;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReleasePeriodsData &&
          other.learnerId == learnerId &&
          other.periods == periods;

  @override
  int get hashCode => learnerId.hashCode + periods.hashCode;

  factory AcademicReleasePeriodsData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReleasePeriodsDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReleasePeriodsDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
