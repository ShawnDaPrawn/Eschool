// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'peer_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PeerConfiguration _$PeerConfigurationFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PeerConfiguration',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'persona',
            'timezone',
            'writable',
            'groups',
            'children',
            'can_award_points',
            'point_categories',
          ],
        );
        final val = PeerConfiguration(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          persona: $checkedConvert(
            'persona',
            (v) => $enumDecode(
              _$PeerConfigurationPersonaEnumEnumMap,
              v,
              unknownValue: PeerConfigurationPersonaEnum.unknownDefaultOpenApi,
            ),
          ),
          timezone: $checkedConvert('timezone', (v) => v as String),
          writable: $checkedConvert('writable', (v) => v as bool),
          groups: $checkedConvert(
            'groups',
            (v) => (v as List<dynamic>)
                .map((e) => PeerChoice.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          children: $checkedConvert(
            'children',
            (v) => (v as List<dynamic>)
                .map((e) => PeerChoice.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          canAwardPoints: $checkedConvert('can_award_points', (v) => v as bool),
          pointCategories: $checkedConvert(
            'point_categories',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => PeerPointCategory.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'canAwardPoints': 'can_award_points',
        'pointCategories': 'point_categories',
      },
    );

Map<String, dynamic> _$PeerConfigurationToJson(
  PeerConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'persona': _$PeerConfigurationPersonaEnumEnumMap[instance.persona]!,
  'timezone': instance.timezone,
  'writable': instance.writable,
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'children': instance.children.map((e) => e.toJson()).toList(),
  'can_award_points': instance.canAwardPoints,
  'point_categories': instance.pointCategories.map((e) => e.toJson()).toList(),
};

const _$PeerConfigurationPersonaEnumEnumMap = {
  PeerConfigurationPersonaEnum.staff: 'staff',
  PeerConfigurationPersonaEnum.parent: 'parent',
  PeerConfigurationPersonaEnum.student: 'student',
  PeerConfigurationPersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
