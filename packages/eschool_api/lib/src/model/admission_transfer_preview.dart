//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_transfer_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionTransferPreview {
  /// Returns a new [AdmissionTransferPreview] instance.
  AdmissionTransferPreview({
    required this.schoolId,

    required this.actorUserId,

    required this.ticketId,

    required this.revision,

    required this.recipientId,

    required this.recipientName,

    required this.kind,

    required this.eventName,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'ticket_id', required: true, includeIfNull: false)
  final int ticketId;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'recipient_id', required: true, includeIfNull: false)
  final int recipientId;

  @JsonKey(name: r'recipient_name', required: true, includeIfNull: false)
  final String recipientName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionTransferPreviewKindEnum.unknownDefaultOpenApi,
  )
  final AdmissionTransferPreviewKindEnum kind;

  @JsonKey(name: r'event_name', required: true, includeIfNull: false)
  final String eventName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionTransferPreview &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.ticketId == ticketId &&
          other.revision == revision &&
          other.recipientId == recipientId &&
          other.recipientName == recipientName &&
          other.kind == kind &&
          other.eventName == eventName;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      ticketId.hashCode +
      revision.hashCode +
      recipientId.hashCode +
      recipientName.hashCode +
      kind.hashCode +
      eventName.hashCode;

  factory AdmissionTransferPreview.fromJson(Map<String, dynamic> json) =>
      _$AdmissionTransferPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionTransferPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionTransferPreviewKindEnum {
  @JsonValue(r'general')
  general(r'general'),
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionTransferPreviewKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
