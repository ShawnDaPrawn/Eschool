// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_decision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryDecision _$GalleryDecisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryDecision', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['operation_id', 'revision', 'decision'],
      );
      final val = GalleryDecision(
        operationId: $checkedConvert('operation_id', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        decision: $checkedConvert(
          'decision',
          (v) => $enumDecode(
            _$GalleryDecisionDecisionEnumEnumMap,
            v,
            unknownValue: GalleryDecisionDecisionEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GalleryDecisionToJson(GalleryDecision instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'decision': _$GalleryDecisionDecisionEnumEnumMap[instance.decision]!,
    };

const _$GalleryDecisionDecisionEnumEnumMap = {
  GalleryDecisionDecisionEnum.approve: 'approve',
  GalleryDecisionDecisionEnum.reject: 'reject',
  GalleryDecisionDecisionEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
