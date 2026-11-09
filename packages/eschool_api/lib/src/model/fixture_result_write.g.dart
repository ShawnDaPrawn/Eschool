// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_result_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureResultWrite _$FixtureResultWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureResultWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['operation_id', 'host_score', 'guest_score'],
        );
        final val = FixtureResultWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          hostScore: $checkedConvert('host_score', (v) => (v as num).toInt()),
          guestScore: $checkedConvert('guest_score', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'hostScore': 'host_score',
        'guestScore': 'guest_score',
      },
    );

Map<String, dynamic> _$FixtureResultWriteToJson(FixtureResultWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'host_score': instance.hostScore,
      'guest_score': instance.guestScore,
    };
