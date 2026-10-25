// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryConfiguration _$MasteryConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'MasteryConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'can_manage',
        'settings',
        'groups',
      ],
    );
    final val = MasteryConfiguration(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      settings: $checkedConvert(
        'settings',
        (v) => MasterySettings.fromJson(v as Map<String, dynamic>),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map((e) => MasteryGroup.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'canManage': 'can_manage',
  },
);

Map<String, dynamic> _$MasteryConfigurationToJson(
  MasteryConfiguration instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'can_manage': instance.canManage,
  'settings': instance.settings.toJson(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
};
