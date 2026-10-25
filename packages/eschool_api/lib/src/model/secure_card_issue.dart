//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_issue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardIssue {
  /// Returns a new [SecureCardIssue] instance.
  SecureCardIssue({
    required this.operationId,

    required this.personId,

    required this.type,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'person_id', required: true, includeIfNull: false)
  final int personId;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardIssue &&
          other.operationId == operationId &&
          other.personId == personId &&
          other.type == type;

  @override
  int get hashCode => operationId.hashCode + personId.hashCode + type.hashCode;

  factory SecureCardIssue.fromJson(Map<String, dynamic> json) =>
      _$SecureCardIssueFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardIssueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
