// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reasoning_review_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReasoningReviewWrite _$ReasoningReviewWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ReasoningReviewWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'text', 'source', 'from_side'],
    );
    final val = ReasoningReviewWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      text: $checkedConvert('text', (v) => v as String),
      source_: $checkedConvert(
        'source',
        (v) => $enumDecode(
          _$ReasoningReviewWriteSource_EnumEnumMap,
          v,
          unknownValue: ReasoningReviewWriteSource_Enum.unknownDefaultOpenApi,
        ),
      ),
      fromSide: $checkedConvert(
        'from_side',
        (v) => $enumDecode(
          _$ReasoningReviewWriteFromSideEnumEnumMap,
          v,
          unknownValue: ReasoningReviewWriteFromSideEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'source_': 'source',
    'fromSide': 'from_side',
  },
);

Map<String, dynamic> _$ReasoningReviewWriteToJson(
  ReasoningReviewWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'text': instance.text,
  'source': _$ReasoningReviewWriteSource_EnumEnumMap[instance.source_]!,
  'from_side': _$ReasoningReviewWriteFromSideEnumEnumMap[instance.fromSide]!,
};

const _$ReasoningReviewWriteSource_EnumEnumMap = {
  ReasoningReviewWriteSource_Enum.speech: 'speech',
  ReasoningReviewWriteSource_Enum.written: 'written',
  ReasoningReviewWriteSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ReasoningReviewWriteFromSideEnumEnumMap = {
  ReasoningReviewWriteFromSideEnum.ours: 'ours',
  ReasoningReviewWriteFromSideEnum.theirs: 'theirs',
  ReasoningReviewWriteFromSideEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
