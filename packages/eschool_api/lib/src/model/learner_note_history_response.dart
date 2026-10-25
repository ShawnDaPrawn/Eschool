//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_note_history.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_history_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteHistoryResponse {
  /// Returns a new [LearnerNoteHistoryResponse] instance.
  LearnerNoteHistoryResponse({
    required this.error,

    required this.message,

    required this.code,

    required this.data,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 0
  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final int code;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final LearnerNoteHistory data;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteHistoryResponse &&
          other.error == error &&
          other.message == message &&
          other.code == code &&
          other.data == data;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + code.hashCode + data.hashCode;

  factory LearnerNoteHistoryResponse.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteHistoryResponseFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteHistoryResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
