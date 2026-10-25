//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_revision_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationRevisionInput {
  /// Returns a new [ConsultationRevisionInput] instance.
  ConsultationRevisionInput({
    required this.operationId,

    required this.revision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationRevisionInput &&
          other.operationId == operationId &&
          other.revision == revision;

  @override
  int get hashCode => operationId.hashCode + revision.hashCode;

  factory ConsultationRevisionInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationRevisionInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationRevisionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
