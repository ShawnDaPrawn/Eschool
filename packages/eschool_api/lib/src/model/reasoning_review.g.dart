// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_review.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningReview _$ReasoningReviewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ReasoningReview',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'state',
            'source',
            'from_side',
            'stance',
            'requested_by',
            'requested_at',
            'feedback',
            'failure_code',
            'shared',
          ],
        );
        final val = ReasoningReview(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$ReasoningReviewStateEnumEnumMap,
              v,
              unknownValue: ReasoningReviewStateEnum.unknownDefaultOpenApi,
            ),
          ),
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$ReasoningReviewSource_EnumEnumMap,
              v,
              unknownValue: ReasoningReviewSource_Enum.unknownDefaultOpenApi,
            ),
          ),
          fromSide: $checkedConvert(
            'from_side',
            (v) => $enumDecode(
              _$ReasoningReviewFromSideEnumEnumMap,
              v,
              unknownValue: ReasoningReviewFromSideEnum.unknownDefaultOpenApi,
            ),
          ),
          stance: $checkedConvert(
            'stance',
            (v) => $enumDecodeNullable(
              _$ReasoningReviewStanceEnumEnumMap,
              v,
              unknownValue: ReasoningReviewStanceEnum.unknownDefaultOpenApi,
            ),
          ),
          requestedBy: $checkedConvert(
            'requested_by',
            (v) => (v as num).toInt(),
          ),
          requestedAt: $checkedConvert(
            'requested_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          feedback: $checkedConvert(
            'feedback',
            (v) => v == null
                ? null
                : ReasoningFeedback.fromJson(v as Map<String, dynamic>),
          ),
          failureCode: $checkedConvert('failure_code', (v) => v as String?),
          shared: $checkedConvert(
            'shared',
            (v) => v == null
                ? null
                : ReasoningReviewShared.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'source_': 'source',
        'fromSide': 'from_side',
        'requestedBy': 'requested_by',
        'requestedAt': 'requested_at',
        'failureCode': 'failure_code',
      },
    );

Map<String, dynamic> _$ReasoningReviewToJson(ReasoningReview instance) =>
    <String, dynamic>{
      'id': instance.id,
      'state': _$ReasoningReviewStateEnumEnumMap[instance.state]!,
      'source': _$ReasoningReviewSource_EnumEnumMap[instance.source_]!,
      'from_side': _$ReasoningReviewFromSideEnumEnumMap[instance.fromSide]!,
      'stance': _$ReasoningReviewStanceEnumEnumMap[instance.stance],
      'requested_by': instance.requestedBy,
      'requested_at': instance.requestedAt?.toIso8601String(),
      'feedback': instance.feedback?.toJson(),
      'failure_code': instance.failureCode,
      'shared': instance.shared?.toJson(),
    };

const _$ReasoningReviewStateEnumEnumMap = {
  ReasoningReviewStateEnum.pending: 'pending',
  ReasoningReviewStateEnum.completed: 'completed',
  ReasoningReviewStateEnum.unavailable: 'unavailable',
  ReasoningReviewStateEnum.quarantined: 'quarantined',
  ReasoningReviewStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ReasoningReviewSource_EnumEnumMap = {
  ReasoningReviewSource_Enum.speech: 'speech',
  ReasoningReviewSource_Enum.written: 'written',
  ReasoningReviewSource_Enum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ReasoningReviewFromSideEnumEnumMap = {
  ReasoningReviewFromSideEnum.ours: 'ours',
  ReasoningReviewFromSideEnum.theirs: 'theirs',
  ReasoningReviewFromSideEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ReasoningReviewStanceEnumEnumMap = {
  ReasoningReviewStanceEnum.proposition: 'proposition',
  ReasoningReviewStanceEnum.opposition: 'opposition',
  ReasoningReviewStanceEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
