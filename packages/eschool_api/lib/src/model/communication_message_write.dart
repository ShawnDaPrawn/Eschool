//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience_selection.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageWrite {
  /// Returns a new [CommunicationMessageWrite] instance.
  CommunicationMessageWrite({
    required this.operationId,

    required this.revision,

    required this.title,

    required this.markdown,

    required this.channels,

    this.scheduledLocal,

    required this.audience,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final Set<String> channels;

  @JsonKey(name: r'scheduled_local', required: false, includeIfNull: false)
  final String? scheduledLocal;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final CommunicationAudienceSelection audience;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.title == title &&
          other.markdown == markdown &&
          other.channels == channels &&
          other.scheduledLocal == scheduledLocal &&
          other.audience == audience;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      title.hashCode +
      markdown.hashCode +
      channels.hashCode +
      (scheduledLocal == null ? 0 : scheduledLocal.hashCode) +
      audience.hashCode;

  factory CommunicationMessageWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
