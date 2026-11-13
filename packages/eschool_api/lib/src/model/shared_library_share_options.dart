//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/shared_library_share_options_credits_inner.dart';
import 'package:eschool_api/src/model/shared_library_share_options_question.dart';
import 'package:eschool_api/src/model/shared_library_share_options_licence.dart';
import 'package:eschool_api/src/model/shared_library_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_share_options.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryShareOptions {
  /// Returns a new [SharedLibraryShareOptions] instance.
  SharedLibraryShareOptions({
    required this.practiceItemId,

    required this.revision,

    required this.question,

    required this.suggestedTitle,

    required this.topics,

    required this.credits,

    required this.licence,

    required this.sharingEnabled,

    required this.leaderApproval,

    required this.shareable,

    required this.currentContributionId,

    required this.versionId,
  });

  @JsonKey(name: r'practice_item_id', required: true, includeIfNull: false)
  final int practiceItemId;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final SharedLibraryShareOptionsQuestion question;

  @JsonKey(name: r'suggested_title', required: true, includeIfNull: false)
  final String suggestedTitle;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<SharedLibraryTopic> topics;

  @JsonKey(name: r'credits', required: true, includeIfNull: false)
  final List<SharedLibraryShareOptionsCreditsInner> credits;

  @JsonKey(name: r'licence', required: true, includeIfNull: false)
  final SharedLibraryShareOptionsLicence licence;

  @JsonKey(name: r'sharing_enabled', required: true, includeIfNull: false)
  final bool sharingEnabled;

  @JsonKey(name: r'leader_approval', required: true, includeIfNull: false)
  final bool leaderApproval;

  @JsonKey(name: r'shareable', required: true, includeIfNull: false)
  final bool shareable;

  @JsonKey(
    name: r'current_contribution_id',
    required: true,
    includeIfNull: true,
  )
  final int? currentContributionId;

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryShareOptions &&
          other.practiceItemId == practiceItemId &&
          other.revision == revision &&
          other.question == question &&
          other.suggestedTitle == suggestedTitle &&
          other.topics == topics &&
          other.credits == credits &&
          other.licence == licence &&
          other.sharingEnabled == sharingEnabled &&
          other.leaderApproval == leaderApproval &&
          other.shareable == shareable &&
          other.currentContributionId == currentContributionId &&
          other.versionId == versionId;

  @override
  int get hashCode =>
      practiceItemId.hashCode +
      revision.hashCode +
      question.hashCode +
      suggestedTitle.hashCode +
      topics.hashCode +
      credits.hashCode +
      licence.hashCode +
      sharingEnabled.hashCode +
      leaderApproval.hashCode +
      shareable.hashCode +
      (currentContributionId == null ? 0 : currentContributionId.hashCode) +
      versionId.hashCode;

  factory SharedLibraryShareOptions.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryShareOptionsFromJson(json);

  Map<String, dynamic> toJson() => _$SharedLibraryShareOptionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
