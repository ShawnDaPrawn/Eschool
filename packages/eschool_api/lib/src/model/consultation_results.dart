//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_result_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_results.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationResults {
  /// Returns a new [ConsultationResults] instance.
  ConsultationResults({
    required this.id,

    required this.closed,

    required this.anonymous,

    required this.responseCount,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'closed', required: true, includeIfNull: false)
  final bool closed;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  // minimum: 0
  @JsonKey(name: r'response_count', required: true, includeIfNull: false)
  final int responseCount;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ConsultationResultQuestion> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationResults &&
          other.id == id &&
          other.closed == closed &&
          other.anonymous == anonymous &&
          other.responseCount == responseCount &&
          other.questions == questions;

  @override
  int get hashCode =>
      id.hashCode +
      closed.hashCode +
      anonymous.hashCode +
      responseCount.hashCode +
      questions.hashCode;

  factory ConsultationResults.fromJson(Map<String, dynamic> json) =>
      _$ConsultationResultsFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationResultsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
