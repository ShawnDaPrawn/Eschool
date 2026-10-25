//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_response_answer.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_response_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationResponseEntry {
  /// Returns a new [ConsultationResponseEntry] instance.
  ConsultationResponseEntry({
    required this.respondentUserId,

    required this.respondentName,

    required this.answers,
  });

  // minimum: 1
  @JsonKey(name: r'respondent_user_id', required: true, includeIfNull: true)
  final int? respondentUserId;

  @JsonKey(name: r'respondent_name', required: true, includeIfNull: true)
  final String? respondentName;

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<ConsultationResponseAnswer> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationResponseEntry &&
          other.respondentUserId == respondentUserId &&
          other.respondentName == respondentName &&
          other.answers == answers;

  @override
  int get hashCode =>
      (respondentUserId == null ? 0 : respondentUserId.hashCode) +
      (respondentName == null ? 0 : respondentName.hashCode) +
      answers.hashCode;

  factory ConsultationResponseEntry.fromJson(Map<String, dynamic> json) =>
      _$ConsultationResponseEntryFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationResponseEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
