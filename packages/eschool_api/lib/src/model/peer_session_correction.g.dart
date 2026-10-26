// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_session_correction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerSessionCorrection _$PeerSessionCorrectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PeerSessionCorrection', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['persona', 'operation_id', 'revision', 'reason'],
  );
  final val = PeerSessionCorrection(
    persona: $checkedConvert(
      'persona',
      (v) => $enumDecode(
        _$PeerSessionCorrectionPersonaEnumEnumMap,
        v,
        unknownValue: PeerSessionCorrectionPersonaEnum.unknownDefaultOpenApi,
      ),
    ),
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$PeerSessionCorrectionToJson(
  PeerSessionCorrection instance,
) => <String, dynamic>{
  'persona': _$PeerSessionCorrectionPersonaEnumEnumMap[instance.persona]!,
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
};

const _$PeerSessionCorrectionPersonaEnumEnumMap = {
  PeerSessionCorrectionPersonaEnum.staff: 'staff',
  PeerSessionCorrectionPersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
