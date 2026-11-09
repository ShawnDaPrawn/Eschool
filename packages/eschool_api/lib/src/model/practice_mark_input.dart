//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_mark_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkInput {
  /// Returns a new [PracticeMarkInput] instance.
  PracticeMarkInput({required this.operationId, required this.correct});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkInput &&
          other.operationId == operationId &&
          other.correct == correct;

  @override
  int get hashCode => operationId.hashCode + correct.hashCode;

  factory PracticeMarkInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
