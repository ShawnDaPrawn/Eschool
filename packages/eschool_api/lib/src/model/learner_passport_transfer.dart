//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_transfer_results_inner.dart';
import 'package:eschool_api/src/model/learner_passport_transfer_reports_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_transfer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportTransfer {
  /// Returns a new [LearnerPassportTransfer] instance.
  LearnerPassportTransfer({
    required this.fromSchoolName,

    required this.toSchoolName,

    required this.lastAttendedOn,

    required this.startsOn,

    required this.reports,

    required this.results,
  });

  @JsonKey(name: r'from_school_name', required: true, includeIfNull: false)
  final String fromSchoolName;

  @JsonKey(name: r'to_school_name', required: true, includeIfNull: false)
  final String toSchoolName;

  @JsonKey(name: r'last_attended_on', required: true, includeIfNull: false)
  final DateTime lastAttendedOn;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'reports', required: true, includeIfNull: false)
  final List<LearnerPassportTransferReportsInner> reports;

  @JsonKey(name: r'results', required: true, includeIfNull: false)
  final List<LearnerPassportTransferResultsInner> results;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportTransfer &&
          other.fromSchoolName == fromSchoolName &&
          other.toSchoolName == toSchoolName &&
          other.lastAttendedOn == lastAttendedOn &&
          other.startsOn == startsOn &&
          other.reports == reports &&
          other.results == results;

  @override
  int get hashCode =>
      fromSchoolName.hashCode +
      toSchoolName.hashCode +
      lastAttendedOn.hashCode +
      startsOn.hashCode +
      reports.hashCode +
      results.hashCode;

  factory LearnerPassportTransfer.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportTransferFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportTransferToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
