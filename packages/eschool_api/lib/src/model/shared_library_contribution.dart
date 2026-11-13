//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_rating_summary.dart';
import 'package:eschool_api/src/model/shared_library_contribution_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_contribution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryContribution {
  /// Returns a new [SharedLibraryContribution] instance.
  SharedLibraryContribution({
    required this.id,

    required this.practiceItemId,

    required this.title,

    required this.state,

    required this.status,

    required this.statusLabel,

    required this.credit,

    required this.creditLabel,

    required this.licence,

    required this.teacher,

    required this.mine,

    required this.topic,

    required this.libraryItemId,

    required this.rating,

    required this.declineReason,

    required this.contributedAt,

    required this.canView,

    required this.canDecide,

    required this.canWithdraw,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'practice_item_id', required: true, includeIfNull: false)
  final int practiceItemId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SharedLibraryContributionStateEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryContributionStateEnum state;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SharedLibraryContributionStatusEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryContributionStatusEnum status;

  @JsonKey(name: r'status_label', required: true, includeIfNull: false)
  final String statusLabel;

  @JsonKey(
    name: r'credit',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SharedLibraryContributionCreditEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryContributionCreditEnum credit;

  @JsonKey(name: r'credit_label', required: true, includeIfNull: false)
  final String creditLabel;

  @JsonKey(name: r'licence', required: true, includeIfNull: false)
  final String licence;

  @JsonKey(name: r'teacher', required: true, includeIfNull: false)
  final String teacher;

  @JsonKey(name: r'mine', required: true, includeIfNull: false)
  final bool mine;

  @JsonKey(name: r'topic', required: true, includeIfNull: true)
  final SharedLibraryContributionTopic? topic;

  @JsonKey(name: r'library_item_id', required: true, includeIfNull: true)
  final int? libraryItemId;

  @JsonKey(name: r'rating', required: true, includeIfNull: true)
  final SharedLibraryRatingSummary? rating;

  @JsonKey(name: r'decline_reason', required: true, includeIfNull: true)
  final String? declineReason;

  @JsonKey(name: r'contributed_at', required: true, includeIfNull: true)
  final String? contributedAt;

  @JsonKey(name: r'can_view', required: true, includeIfNull: false)
  final bool canView;

  @JsonKey(name: r'can_decide', required: true, includeIfNull: false)
  final bool canDecide;

  @JsonKey(name: r'can_withdraw', required: true, includeIfNull: false)
  final bool canWithdraw;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryContribution &&
          other.id == id &&
          other.practiceItemId == practiceItemId &&
          other.title == title &&
          other.state == state &&
          other.status == status &&
          other.statusLabel == statusLabel &&
          other.credit == credit &&
          other.creditLabel == creditLabel &&
          other.licence == licence &&
          other.teacher == teacher &&
          other.mine == mine &&
          other.topic == topic &&
          other.libraryItemId == libraryItemId &&
          other.rating == rating &&
          other.declineReason == declineReason &&
          other.contributedAt == contributedAt &&
          other.canView == canView &&
          other.canDecide == canDecide &&
          other.canWithdraw == canWithdraw;

  @override
  int get hashCode =>
      id.hashCode +
      practiceItemId.hashCode +
      title.hashCode +
      state.hashCode +
      status.hashCode +
      statusLabel.hashCode +
      credit.hashCode +
      creditLabel.hashCode +
      licence.hashCode +
      teacher.hashCode +
      mine.hashCode +
      (topic == null ? 0 : topic.hashCode) +
      (libraryItemId == null ? 0 : libraryItemId.hashCode) +
      (rating == null ? 0 : rating.hashCode) +
      (declineReason == null ? 0 : declineReason.hashCode) +
      (contributedAt == null ? 0 : contributedAt.hashCode) +
      canView.hashCode +
      canDecide.hashCode +
      canWithdraw.hashCode;

  factory SharedLibraryContribution.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryContributionFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryContributionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SharedLibraryContributionStateEnum {
  @JsonValue(r'awaiting_approval')
  awaitingApproval(r'awaiting_approval'),
  @JsonValue(r'shared')
  shared(r'shared'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryContributionStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SharedLibraryContributionStatusEnum {
  @JsonValue(r'awaiting_approval')
  awaitingApproval(r'awaiting_approval'),
  @JsonValue(r'declined')
  declined(r'declined'),
  @JsonValue(r'in_review')
  inReview(r'in_review'),
  @JsonValue(r'not_accepted')
  notAccepted(r'not_accepted'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'unpublished')
  unpublished(r'unpublished'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryContributionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SharedLibraryContributionCreditEnum {
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'anonymous')
  anonymous(r'anonymous'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryContributionCreditEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
