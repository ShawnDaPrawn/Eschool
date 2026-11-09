//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_alert_confirm.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyAlertConfirm {
  /// Returns a new [EmergencyAlertConfirm] instance.
  EmergencyAlertConfirm({
    required this.operationId,

    required this.revision,

    required this.confirmation,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  /// The alert's confirmation_word.
  @JsonKey(name: r'confirmation', required: true, includeIfNull: false)
  final String confirmation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyAlertConfirm &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.confirmation == confirmation;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + confirmation.hashCode;

  factory EmergencyAlertConfirm.fromJson(Map<String, dynamic> json) =>
      _$EmergencyAlertConfirmFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyAlertConfirmToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
