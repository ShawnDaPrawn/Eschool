// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_complete.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionComplete _$PeerSessionCompleteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PeerSessionComplete',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['persona', 'operation_id', 'revision', 'minutes'],
    );
    final val = PeerSessionComplete(
      persona: $checkedConvert(
        'persona',
        (v) => $enumDecode(
          _$PeerSessionCompletePersonaEnumEnumMap,
          v,
          unknownValue: PeerSessionCompletePersonaEnum.unknownDefaultOpenApi,
        ),
      ),
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      minutes: $checkedConvert('minutes', (v) => (v as num).toInt()),
      pointCategoryId: $checkedConvert(
        'point_category_id',
        (v) => (v as num?)?.toInt(),
      ),
      points: $checkedConvert('points', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'pointCategoryId': 'point_category_id',
  },
);

Map<String, dynamic> _$PeerSessionCompleteToJson(
  PeerSessionComplete instance,
) => <String, dynamic>{
  'persona': _$PeerSessionCompletePersonaEnumEnumMap[instance.persona]!,
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'minutes': instance.minutes,
  'point_category_id': ?instance.pointCategoryId,
  'points': ?instance.points,
};

const _$PeerSessionCompletePersonaEnumEnumMap = {
  PeerSessionCompletePersonaEnum.staff: 'staff',
  PeerSessionCompletePersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
