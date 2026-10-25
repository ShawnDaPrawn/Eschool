//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_reply_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentReplyInput {
  /// Returns a new [IncidentReplyInput] instance.
  IncidentReplyInput({
    required this.secret,

    required this.operationId,

    required this.body,
  });

  @JsonKey(name: r'secret', required: true, includeIfNull: false)
  final String secret;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentReplyInput &&
          other.secret == secret &&
          other.operationId == operationId &&
          other.body == body;

  @override
  int get hashCode => secret.hashCode + operationId.hashCode + body.hashCode;

  factory IncidentReplyInput.fromJson(Map<String, dynamic> json) =>
      _$IncidentReplyInputFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentReplyInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
