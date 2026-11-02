//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_report_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageReportItemsInner {
  /// Returns a new [CommunicationMessageReportItemsInner] instance.
  CommunicationMessageReportItemsInner({
    required this.name,

    required this.readAt,

    required this.notice,

    required this.email,

    required this.push,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'read_at', required: true, includeIfNull: true)
  final DateTime? readAt;

  @JsonKey(
    name: r'notice',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationMessageReportItemsInnerNoticeEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageReportItemsInnerNoticeEnum notice;

  @JsonKey(
    name: r'email',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        CommunicationMessageReportItemsInnerEmailEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageReportItemsInnerEmailEnum? email;

  @JsonKey(
    name: r'push',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        CommunicationMessageReportItemsInnerPushEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageReportItemsInnerPushEnum? push;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageReportItemsInner &&
          other.name == name &&
          other.readAt == readAt &&
          other.notice == notice &&
          other.email == email &&
          other.push == push;

  @override
  int get hashCode =>
      name.hashCode +
      (readAt == null ? 0 : readAt.hashCode) +
      notice.hashCode +
      (email == null ? 0 : email.hashCode) +
      (push == null ? 0 : push.hashCode);

  factory CommunicationMessageReportItemsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$CommunicationMessageReportItemsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CommunicationMessageReportItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationMessageReportItemsInnerNoticeEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'queued')
  queued(r'queued'),
  @JsonValue(r'none')
  none(r'none'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageReportItemsInnerNoticeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CommunicationMessageReportItemsInnerEmailEnum {
  @JsonValue(r'delivered')
  delivered(r'delivered'),
  @JsonValue(r'sent')
  sent(r'sent'),
  @JsonValue(r'queued')
  queued(r'queued'),
  @JsonValue(r'bounced')
  bounced(r'bounced'),
  @JsonValue(r'complained')
  complained(r'complained'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'suppressed')
  suppressed(r'suppressed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageReportItemsInnerEmailEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CommunicationMessageReportItemsInnerPushEnum {
  @JsonValue(r'delivered')
  delivered(r'delivered'),
  @JsonValue(r'sent')
  sent(r'sent'),
  @JsonValue(r'queued')
  queued(r'queued'),
  @JsonValue(r'bounced')
  bounced(r'bounced'),
  @JsonValue(r'complained')
  complained(r'complained'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'suppressed')
  suppressed(r'suppressed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageReportItemsInnerPushEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
