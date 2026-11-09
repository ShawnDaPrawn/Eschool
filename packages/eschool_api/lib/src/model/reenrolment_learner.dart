//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_learner_contracts_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentLearner {
  /// Returns a new [ReenrolmentLearner] instance.
  ReenrolmentLearner({required this.learnerId, required this.contracts});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'contracts', required: true, includeIfNull: false)
  final List<ReenrolmentLearnerContractsInner> contracts;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentLearner &&
          other.learnerId == learnerId &&
          other.contracts == contracts;

  @override
  int get hashCode => learnerId.hashCode + contracts.hashCode;

  factory ReenrolmentLearner.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
