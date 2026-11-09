//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_group_learners_inner_topics_inner.dart';
import 'package:eschool_api/src/model/diagnostic_status.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_group_learners_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticGroupLearnersInner {
  /// Returns a new [DiagnosticGroupLearnersInner] instance.
  DiagnosticGroupLearnersInner({
    required this.learnerId,

    required this.name,

    required this.status,

    required this.topics,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiagnosticStatus.unknownDefaultOpenApi,
  )
  final DiagnosticStatus status;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<DiagnosticGroupLearnersInnerTopicsInner> topics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticGroupLearnersInner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.status == status &&
          other.topics == topics;

  @override
  int get hashCode =>
      learnerId.hashCode + name.hashCode + status.hashCode + topics.hashCode;

  factory DiagnosticGroupLearnersInner.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticGroupLearnersInnerFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticGroupLearnersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
