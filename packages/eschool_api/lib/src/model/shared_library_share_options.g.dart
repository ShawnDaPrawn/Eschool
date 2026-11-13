// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_share_options.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryShareOptions _$SharedLibraryShareOptionsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryShareOptions',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'practice_item_id',
        'revision',
        'question',
        'suggested_title',
        'topics',
        'credits',
        'licence',
        'sharing_enabled',
        'leader_approval',
        'shareable',
        'current_contribution_id',
        'version_id',
      ],
    );
    final val = SharedLibraryShareOptions(
      practiceItemId: $checkedConvert(
        'practice_item_id',
        (v) => (v as num).toInt(),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      question: $checkedConvert(
        'question',
        (v) => SharedLibraryShareOptionsQuestion.fromJson(
          v as Map<String, dynamic>,
        ),
      ),
      suggestedTitle: $checkedConvert('suggested_title', (v) => v as String),
      topics: $checkedConvert(
        'topics',
        (v) => (v as List<dynamic>)
            .map((e) => SharedLibraryTopic.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      credits: $checkedConvert(
        'credits',
        (v) => (v as List<dynamic>)
            .map(
              (e) => SharedLibraryShareOptionsCreditsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      licence: $checkedConvert(
        'licence',
        (v) => SharedLibraryShareOptionsLicence.fromJson(
          v as Map<String, dynamic>,
        ),
      ),
      sharingEnabled: $checkedConvert('sharing_enabled', (v) => v as bool),
      leaderApproval: $checkedConvert('leader_approval', (v) => v as bool),
      shareable: $checkedConvert('shareable', (v) => v as bool),
      currentContributionId: $checkedConvert(
        'current_contribution_id',
        (v) => (v as num?)?.toInt(),
      ),
      versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'practiceItemId': 'practice_item_id',
    'suggestedTitle': 'suggested_title',
    'sharingEnabled': 'sharing_enabled',
    'leaderApproval': 'leader_approval',
    'currentContributionId': 'current_contribution_id',
    'versionId': 'version_id',
  },
);

Map<String, dynamic> _$SharedLibraryShareOptionsToJson(
  SharedLibraryShareOptions instance,
) => <String, dynamic>{
  'practice_item_id': instance.practiceItemId,
  'revision': instance.revision,
  'question': instance.question.toJson(),
  'suggested_title': instance.suggestedTitle,
  'topics': instance.topics.map((e) => e.toJson()).toList(),
  'credits': instance.credits.map((e) => e.toJson()).toList(),
  'licence': instance.licence.toJson(),
  'sharing_enabled': instance.sharingEnabled,
  'leader_approval': instance.leaderApproval,
  'shareable': instance.shareable,
  'current_contribution_id': instance.currentContributionId,
  'version_id': instance.versionId,
};
