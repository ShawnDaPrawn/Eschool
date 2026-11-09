//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_transfer_reports_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportTransferReportsInner {
  /// Returns a new [LearnerPassportTransferReportsInner] instance.
  LearnerPassportTransferReportsInner({
    required this.yearName,

    required this.termName,
  });

  @JsonKey(name: r'year_name', required: true, includeIfNull: false)
  final String yearName;

  @JsonKey(name: r'term_name', required: true, includeIfNull: false)
  final String termName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportTransferReportsInner &&
          other.yearName == yearName &&
          other.termName == termName;

  @override
  int get hashCode => yearName.hashCode + termName.hashCode;

  factory LearnerPassportTransferReportsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerPassportTransferReportsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerPassportTransferReportsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
