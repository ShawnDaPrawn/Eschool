//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_alert.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyAlert {
  /// Returns a new [EmergencyAlert] instance.
  EmergencyAlert({
    required this.id,

    required this.kind,

    required this.audience,

    required this.drill,

    required this.status,

    required this.title,

    required this.message,

    required this.revision,

    required this.drillId,

    required this.preparedBy,

    required this.preparedAt,

    required this.confirmedBy,

    required this.activatedAt,

    required this.allClearBy,

    required this.allClearAt,

    required this.allClearMessage,

    required this.cancelledAt,

    required this.recipientCount,

    required this.confirmationWord,

    required this.confirmBy,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertKindEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertKindEnum kind;

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertAudienceEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertAudienceEnum audience;

  @JsonKey(name: r'drill', required: true, includeIfNull: false)
  final bool drill;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: EmergencyAlertStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyAlertStatusEnum status;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'drill_id', required: true, includeIfNull: true)
  final String? drillId;

  // minimum: 1
  @JsonKey(name: r'prepared_by', required: true, includeIfNull: false)
  final int preparedBy;

  @JsonKey(name: r'prepared_at', required: true, includeIfNull: false)
  final DateTime preparedAt;

  // minimum: 1
  @JsonKey(name: r'confirmed_by', required: true, includeIfNull: true)
  final int? confirmedBy;

  @JsonKey(name: r'activated_at', required: true, includeIfNull: true)
  final DateTime? activatedAt;

  // minimum: 1
  @JsonKey(name: r'all_clear_by', required: true, includeIfNull: true)
  final int? allClearBy;

  @JsonKey(name: r'all_clear_at', required: true, includeIfNull: true)
  final DateTime? allClearAt;

  @JsonKey(name: r'all_clear_message', required: true, includeIfNull: true)
  final String? allClearMessage;

  @JsonKey(name: r'cancelled_at', required: true, includeIfNull: true)
  final DateTime? cancelledAt;

  // minimum: 0
  @JsonKey(name: r'recipient_count', required: true, includeIfNull: false)
  final int recipientCount;

  /// The word to type to confirm a pending alert (step two); null once it is sent or cancelled.
  @JsonKey(name: r'confirmation_word', required: true, includeIfNull: true)
  final String? confirmationWord;

  /// A pending alert must be confirmed before this time.
  @JsonKey(name: r'confirm_by', required: true, includeIfNull: true)
  final DateTime? confirmBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyAlert &&
          other.id == id &&
          other.kind == kind &&
          other.audience == audience &&
          other.drill == drill &&
          other.status == status &&
          other.title == title &&
          other.message == message &&
          other.revision == revision &&
          other.drillId == drillId &&
          other.preparedBy == preparedBy &&
          other.preparedAt == preparedAt &&
          other.confirmedBy == confirmedBy &&
          other.activatedAt == activatedAt &&
          other.allClearBy == allClearBy &&
          other.allClearAt == allClearAt &&
          other.allClearMessage == allClearMessage &&
          other.cancelledAt == cancelledAt &&
          other.recipientCount == recipientCount &&
          other.confirmationWord == confirmationWord &&
          other.confirmBy == confirmBy;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      audience.hashCode +
      drill.hashCode +
      status.hashCode +
      title.hashCode +
      message.hashCode +
      revision.hashCode +
      (drillId == null ? 0 : drillId.hashCode) +
      preparedBy.hashCode +
      preparedAt.hashCode +
      (confirmedBy == null ? 0 : confirmedBy.hashCode) +
      (activatedAt == null ? 0 : activatedAt.hashCode) +
      (allClearBy == null ? 0 : allClearBy.hashCode) +
      (allClearAt == null ? 0 : allClearAt.hashCode) +
      (allClearMessage == null ? 0 : allClearMessage.hashCode) +
      (cancelledAt == null ? 0 : cancelledAt.hashCode) +
      recipientCount.hashCode +
      (confirmationWord == null ? 0 : confirmationWord.hashCode) +
      (confirmBy == null ? 0 : confirmBy.hashCode);

  factory EmergencyAlert.fromJson(Map<String, dynamic> json) =>
      _$EmergencyAlertFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyAlertToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyAlertKindEnum {
  @JsonValue(r'lockdown')
  lockdown(r'lockdown'),
  @JsonValue(r'evacuation')
  evacuation(r'evacuation'),
  @JsonValue(r'shelter')
  shelter(r'shelter'),
  @JsonValue(r'critical')
  critical(r'critical'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyAlertKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyAlertAudienceEnum {
  @JsonValue(r'everyone')
  everyone(r'everyone'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyAlertAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyAlertStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'all_clear')
  allClear(r'all_clear'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyAlertStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
