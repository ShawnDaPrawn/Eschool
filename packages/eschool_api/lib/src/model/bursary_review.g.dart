// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryReview _$BursaryReviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'BursaryReview',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'learner_id',
        'session_year_id',
        'session_year',
        'state',
        'revision',
        'submitted_at',
        'decided_at',
        'award_kind',
        'award_value',
        'currency',
        'erased',
        'learner_name',
        'guardian_name',
        'household',
        'documents',
        'fee_discount_id',
        'events',
      ],
    );
    final val = BursaryReview(
      id: $checkedConvert('id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      sessionYear: $checkedConvert('session_year', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$BursaryReviewStateEnumEnumMap,
          v,
          unknownValue: BursaryReviewStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      submittedAt: $checkedConvert(
        'submitted_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      decidedAt: $checkedConvert(
        'decided_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      awardKind: $checkedConvert(
        'award_kind',
        (v) => $enumDecodeNullable(
          _$BursaryReviewAwardKindEnumEnumMap,
          v,
          unknownValue: BursaryReviewAwardKindEnum.unknownDefaultOpenApi,
        ),
      ),
      awardValue: $checkedConvert('award_value', (v) => (v as num?)?.toInt()),
      currency: $checkedConvert('currency', (v) => v as String?),
      erased: $checkedConvert('erased', (v) => v as bool),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      guardianName: $checkedConvert('guardian_name', (v) => v as String),
      household: $checkedConvert(
        'household',
        (v) => v == null
            ? null
            : BursaryApplicationHousehold.fromJson(v as Map<String, dynamic>),
      ),
      documents: $checkedConvert(
        'documents',
        (v) => (v as List<dynamic>)
            .map(
              (e) => BursaryApplicationDocumentsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      feeDiscountId: $checkedConvert(
        'fee_discount_id',
        (v) => (v as num?)?.toInt(),
      ),
      events: $checkedConvert(
        'events',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  BursaryReviewEventsInner.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
    'sessionYear': 'session_year',
    'submittedAt': 'submitted_at',
    'decidedAt': 'decided_at',
    'awardKind': 'award_kind',
    'awardValue': 'award_value',
    'learnerName': 'learner_name',
    'guardianName': 'guardian_name',
    'feeDiscountId': 'fee_discount_id',
  },
);

Map<String, dynamic> _$BursaryReviewToJson(BursaryReview instance) =>
    <String, dynamic>{
      'id': instance.id,
      'learner_id': instance.learnerId,
      'session_year_id': instance.sessionYearId,
      'session_year': instance.sessionYear,
      'state': _$BursaryReviewStateEnumEnumMap[instance.state]!,
      'revision': instance.revision,
      'submitted_at': instance.submittedAt?.toIso8601String(),
      'decided_at': instance.decidedAt?.toIso8601String(),
      'award_kind': _$BursaryReviewAwardKindEnumEnumMap[instance.awardKind],
      'award_value': instance.awardValue,
      'currency': instance.currency,
      'erased': instance.erased,
      'learner_name': instance.learnerName,
      'guardian_name': instance.guardianName,
      'household': instance.household?.toJson(),
      'documents': instance.documents.map((e) => e.toJson()).toList(),
      'fee_discount_id': instance.feeDiscountId,
      'events': instance.events.map((e) => e.toJson()).toList(),
    };

const _$BursaryReviewStateEnumEnumMap = {
  BursaryReviewStateEnum.draft: 'draft',
  BursaryReviewStateEnum.submitted: 'submitted',
  BursaryReviewStateEnum.awarded: 'awarded',
  BursaryReviewStateEnum.declined: 'declined',
  BursaryReviewStateEnum.withdrawn: 'withdrawn',
  BursaryReviewStateEnum.expired: 'expired',
  BursaryReviewStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$BursaryReviewAwardKindEnumEnumMap = {
  BursaryReviewAwardKindEnum.fixed: 'fixed',
  BursaryReviewAwardKindEnum.percent: 'percent',
  BursaryReviewAwardKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
