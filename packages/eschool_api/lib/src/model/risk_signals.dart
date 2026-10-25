//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_signal_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_signals.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskSignals {
  /// Returns a new [RiskSignals] instance.
  RiskSignals({
    required this.sessionYearId,

    required this.page,

    required this.lastPage,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<RiskSignalLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskSignals &&
          other.sessionYearId == sessionYearId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.learners == learners;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      learners.hashCode;

  factory RiskSignals.fromJson(Map<String, dynamic> json) =>
      _$RiskSignalsFromJson(json);

  Map<String, dynamic> toJson() => _$RiskSignalsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
