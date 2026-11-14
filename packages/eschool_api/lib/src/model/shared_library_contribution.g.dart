// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contribution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContribution _$SharedLibraryContributionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SharedLibraryContribution',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'practice_item_id',
        'title',
        'state',
        'status',
        'status_label',
        'credit',
        'credit_label',
        'licence',
        'teacher',
        'mine',
        'topic',
        'library_item_id',
        'rating',
        'decline_reason',
        'contributed_at',
        'can_view',
        'can_decide',
        'can_withdraw',
      ],
    );
    final val = SharedLibraryContribution(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      practiceItemId: $checkedConvert(
        'practice_item_id',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SharedLibraryContributionStateEnumEnumMap,
          v,
          unknownValue:
              SharedLibraryContributionStateEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$SharedLibraryContributionStatusEnumEnumMap,
          v,
          unknownValue:
              SharedLibraryContributionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      statusLabel: $checkedConvert('status_label', (v) => v as String),
      credit: $checkedConvert(
        'credit',
        (v) => $enumDecode(
          _$SharedLibraryContributionCreditEnumEnumMap,
          v,
          unknownValue:
              SharedLibraryContributionCreditEnum.unknownDefaultOpenApi,
        ),
      ),
      creditLabel: $checkedConvert('credit_label', (v) => v as String),
      licence: $checkedConvert('licence', (v) => v as String),
      teacher: $checkedConvert('teacher', (v) => v as String),
      mine: $checkedConvert('mine', (v) => v as bool),
      topic: $checkedConvert(
        'topic',
        (v) => v == null
            ? null
            : SharedLibraryContributionTopic.fromJson(
                v as Map<String, dynamic>,
              ),
      ),
      libraryItemId: $checkedConvert(
        'library_item_id',
        (v) => (v as num?)?.toInt(),
      ),
      rating: $checkedConvert(
        'rating',
        (v) => v == null
            ? null
            : SharedLibraryRatingSummary.fromJson(v as Map<String, dynamic>),
      ),
      declineReason: $checkedConvert('decline_reason', (v) => v as String?),
      contributedAt: $checkedConvert('contributed_at', (v) => v as String?),
      canView: $checkedConvert('can_view', (v) => v as bool),
      canDecide: $checkedConvert('can_decide', (v) => v as bool),
      canWithdraw: $checkedConvert('can_withdraw', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'practiceItemId': 'practice_item_id',
    'statusLabel': 'status_label',
    'creditLabel': 'credit_label',
    'libraryItemId': 'library_item_id',
    'declineReason': 'decline_reason',
    'contributedAt': 'contributed_at',
    'canView': 'can_view',
    'canDecide': 'can_decide',
    'canWithdraw': 'can_withdraw',
  },
);

Map<String, dynamic> _$SharedLibraryContributionToJson(
  SharedLibraryContribution instance,
) => <String, dynamic>{
  'id': instance.id,
  'practice_item_id': instance.practiceItemId,
  'title': instance.title,
  'state': _$SharedLibraryContributionStateEnumEnumMap[instance.state]!,
  'status': _$SharedLibraryContributionStatusEnumEnumMap[instance.status]!,
  'status_label': instance.statusLabel,
  'credit': _$SharedLibraryContributionCreditEnumEnumMap[instance.credit]!,
  'credit_label': instance.creditLabel,
  'licence': instance.licence,
  'teacher': instance.teacher,
  'mine': instance.mine,
  'topic': instance.topic?.toJson(),
  'library_item_id': instance.libraryItemId,
  'rating': instance.rating?.toJson(),
  'decline_reason': instance.declineReason,
  'contributed_at': instance.contributedAt,
  'can_view': instance.canView,
  'can_decide': instance.canDecide,
  'can_withdraw': instance.canWithdraw,
};

const _$SharedLibraryContributionStateEnumEnumMap = {
  SharedLibraryContributionStateEnum.awaitingApproval: 'awaiting_approval',
  SharedLibraryContributionStateEnum.shared: 'shared',
  SharedLibraryContributionStateEnum.declined: 'declined',
  SharedLibraryContributionStateEnum.withdrawn: 'withdrawn',
  SharedLibraryContributionStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$SharedLibraryContributionStatusEnumEnumMap = {
  SharedLibraryContributionStatusEnum.awaitingApproval: 'awaiting_approval',
  SharedLibraryContributionStatusEnum.declined: 'declined',
  SharedLibraryContributionStatusEnum.inReview: 'in_review',
  SharedLibraryContributionStatusEnum.notAccepted: 'not_accepted',
  SharedLibraryContributionStatusEnum.published: 'published',
  SharedLibraryContributionStatusEnum.unpublished: 'unpublished',
  SharedLibraryContributionStatusEnum.withdrawn: 'withdrawn',
  SharedLibraryContributionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$SharedLibraryContributionCreditEnumEnumMap = {
  SharedLibraryContributionCreditEnum.named: 'named',
  SharedLibraryContributionCreditEnum.school: 'school',
  SharedLibraryContributionCreditEnum.anonymous: 'anonymous',
  SharedLibraryContributionCreditEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
