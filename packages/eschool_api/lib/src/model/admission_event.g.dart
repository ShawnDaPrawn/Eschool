// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_event.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionEvent _$AdmissionEventFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AdmissionEvent', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'name',
          'description',
          'status',
          'starts_at',
          'ends_at',
        ],
      );
      final val = AdmissionEvent(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        description: $checkedConvert('description', (v) => v as String?),
        status: $checkedConvert(
          'status',
          (v) => $enumDecode(
            _$AdmissionEventStatusEnumEnumMap,
            v,
            unknownValue: AdmissionEventStatusEnum.unknownDefaultOpenApi,
          ),
        ),
        startsAt: $checkedConvert(
          'starts_at',
          (v) => DateTime.parse(v as String),
        ),
        endsAt: $checkedConvert('ends_at', (v) => DateTime.parse(v as String)),
      );
      return val;
    }, fieldKeyMap: const {'startsAt': 'starts_at', 'endsAt': 'ends_at'});

Map<String, dynamic> _$AdmissionEventToJson(AdmissionEvent instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'description': instance.description,
      'status': _$AdmissionEventStatusEnumEnumMap[instance.status]!,
      'starts_at': instance.startsAt.toIso8601String(),
      'ends_at': instance.endsAt.toIso8601String(),
    };

const _$AdmissionEventStatusEnumEnumMap = {
  AdmissionEventStatusEnum.draft: 'draft',
  AdmissionEventStatusEnum.active: 'active',
  AdmissionEventStatusEnum.paused: 'paused',
  AdmissionEventStatusEnum.cancelled: 'cancelled',
  AdmissionEventStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
