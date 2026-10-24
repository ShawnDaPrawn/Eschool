//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessagePreview {
  /// Returns a new [CommunicationMessagePreview] instance.
  CommunicationMessagePreview({
    required this.id,

    required this.revision,

    required this.recipientCount,

    required this.requiresApproval,

    required this.fingerprint,

    required this.html,

    required this.channels,

    required this.scheduledAt,

    required this.timezone,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  @JsonKey(name: r'recipient_count', required: true, includeIfNull: false)
  final int recipientCount;

  @JsonKey(name: r'requires_approval', required: true, includeIfNull: false)
  final bool requiresApproval;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'html', required: true, includeIfNull: false)
  final String html;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final Set<String> channels;

  @JsonKey(name: r'scheduled_at', required: true, includeIfNull: true)
  final DateTime? scheduledAt;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessagePreview &&
          other.id == id &&
          other.revision == revision &&
          other.recipientCount == recipientCount &&
          other.requiresApproval == requiresApproval &&
          other.fingerprint == fingerprint &&
          other.html == html &&
          other.channels == channels &&
          other.scheduledAt == scheduledAt &&
          other.timezone == timezone;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      recipientCount.hashCode +
      requiresApproval.hashCode +
      fingerprint.hashCode +
      html.hashCode +
      channels.hashCode +
      (scheduledAt == null ? 0 : scheduledAt.hashCode) +
      timezone.hashCode;

  factory CommunicationMessagePreview.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessagePreviewFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessagePreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
