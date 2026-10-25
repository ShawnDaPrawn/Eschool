//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_turn_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorTurnWrite {
  /// Returns a new [TutorTurnWrite] instance.
  TutorTurnWrite({
    required this.operationId,

    required this.question,

    this.working,

    this.photoIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final String question;

  @JsonKey(name: r'working', required: false, includeIfNull: false)
  final String? working;

  @JsonKey(name: r'photo_ids', required: false, includeIfNull: false)
  final Set<String>? photoIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorTurnWrite &&
          other.operationId == operationId &&
          other.question == question &&
          other.working == working &&
          other.photoIds == photoIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      question.hashCode +
      (working == null ? 0 : working.hashCode) +
      photoIds.hashCode;

  factory TutorTurnWrite.fromJson(Map<String, dynamic> json) =>
      _$TutorTurnWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TutorTurnWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
