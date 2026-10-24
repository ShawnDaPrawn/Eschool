//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience_snapshot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_message_editor.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationMessageEditor {
  /// Returns a new [CommunicationMessageEditor] instance.
  CommunicationMessageEditor({
    required this.id,

    required this.title,

    required this.state,

    required this.revision,

    required this.publishedAt,

    required this.scheduledAt,

    required this.updatedAt,

    required this.markdown,

    required this.audience,

    required this.channels,

    required this.scheduledLocal,

    required this.requiresApproval,

    required this.submittedAt,

    required this.approvedAt,

    required this.decisionReason,

    required this.blockedCode,

    required this.withdrawalReason,

    required this.isAuthor,

    required this.canReview,

    required this.recipientCount,

    required this.readCount,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommunicationMessageEditorStateEnum.unknownDefaultOpenApi,
  )
  final CommunicationMessageEditorStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @JsonKey(name: r'scheduled_at', required: true, includeIfNull: true)
  final DateTime? scheduledAt;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: false)
  final DateTime updatedAt;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'audience', required: true, includeIfNull: false)
  final CommunicationAudienceSnapshot audience;

  @JsonKey(name: r'channels', required: true, includeIfNull: false)
  final Set<String> channels;

  @JsonKey(name: r'scheduled_local', required: true, includeIfNull: true)
  final String? scheduledLocal;

  @JsonKey(name: r'requires_approval', required: true, includeIfNull: false)
  final bool requiresApproval;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @JsonKey(name: r'approved_at', required: true, includeIfNull: true)
  final DateTime? approvedAt;

  @JsonKey(name: r'decision_reason', required: true, includeIfNull: true)
  final String? decisionReason;

  @JsonKey(name: r'blocked_code', required: true, includeIfNull: true)
  final String? blockedCode;

  @JsonKey(name: r'withdrawal_reason', required: true, includeIfNull: true)
  final String? withdrawalReason;

  @JsonKey(name: r'is_author', required: true, includeIfNull: false)
  final bool isAuthor;

  @JsonKey(name: r'can_review', required: true, includeIfNull: false)
  final bool canReview;

  // minimum: 0
  @JsonKey(name: r'recipient_count', required: true, includeIfNull: false)
  final int recipientCount;

  // minimum: 0
  @JsonKey(name: r'read_count', required: true, includeIfNull: false)
  final int readCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationMessageEditor &&
          other.id == id &&
          other.title == title &&
          other.state == state &&
          other.revision == revision &&
          other.publishedAt == publishedAt &&
          other.scheduledAt == scheduledAt &&
          other.updatedAt == updatedAt &&
          other.markdown == markdown &&
          other.audience == audience &&
          other.channels == channels &&
          other.scheduledLocal == scheduledLocal &&
          other.requiresApproval == requiresApproval &&
          other.submittedAt == submittedAt &&
          other.approvedAt == approvedAt &&
          other.decisionReason == decisionReason &&
          other.blockedCode == blockedCode &&
          other.withdrawalReason == withdrawalReason &&
          other.isAuthor == isAuthor &&
          other.canReview == canReview &&
          other.recipientCount == recipientCount &&
          other.readCount == readCount;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      state.hashCode +
      revision.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode) +
      (scheduledAt == null ? 0 : scheduledAt.hashCode) +
      updatedAt.hashCode +
      markdown.hashCode +
      audience.hashCode +
      channels.hashCode +
      (scheduledLocal == null ? 0 : scheduledLocal.hashCode) +
      requiresApproval.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode) +
      (approvedAt == null ? 0 : approvedAt.hashCode) +
      (decisionReason == null ? 0 : decisionReason.hashCode) +
      (blockedCode == null ? 0 : blockedCode.hashCode) +
      (withdrawalReason == null ? 0 : withdrawalReason.hashCode) +
      isAuthor.hashCode +
      canReview.hashCode +
      recipientCount.hashCode +
      readCount.hashCode;

  factory CommunicationMessageEditor.fromJson(Map<String, dynamic> json) =>
      _$CommunicationMessageEditorFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationMessageEditorToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationMessageEditorStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'dispatching')
  dispatching(r'dispatching'),
  @JsonValue(r'sent')
  sent(r'sent'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'blocked')
  blocked(r'blocked'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationMessageEditorStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
