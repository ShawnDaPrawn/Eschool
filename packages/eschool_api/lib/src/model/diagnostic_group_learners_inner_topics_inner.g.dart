// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'diagnostic_group_learners_inner_topics_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DiagnosticGroupLearnersInnerTopicsInner
_$DiagnosticGroupLearnersInnerTopicsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DiagnosticGroupLearnersInnerTopicsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['library_topic_id', 'band']);
      final val = DiagnosticGroupLearnersInnerTopicsInner(
        libraryTopicId: $checkedConvert(
          'library_topic_id',
          (v) => (v as num).toInt(),
        ),
        band: $checkedConvert(
          'band',
          (v) => $enumDecode(
            _$DiagnosticBandEnumMap,
            v,
            unknownValue: DiagnosticBand.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'libraryTopicId': 'library_topic_id'});

Map<String, dynamic> _$DiagnosticGroupLearnersInnerTopicsInnerToJson(
  DiagnosticGroupLearnersInnerTopicsInner instance,
) => <String, dynamic>{
  'library_topic_id': instance.libraryTopicId,
  'band': _$DiagnosticBandEnumMap[instance.band]!,
};

const _$DiagnosticBandEnumMap = {
  DiagnosticBand.secure: 'secure',
  DiagnosticBand.developing: 'developing',
  DiagnosticBand.gap: 'gap',
  DiagnosticBand.notReached: 'not_reached',
  DiagnosticBand.unknownDefaultOpenApi: 'unknown_default_open_api',
};
