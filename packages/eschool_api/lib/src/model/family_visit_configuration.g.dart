// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitConfiguration _$FamilyVisitConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyVisitConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'timezone',
        'writable',
        'can_manage',
        'can_host',
        'children',
        'hosts',
        'gate_rules',
      ],
    );
    final val = FamilyVisitConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      timezone: $checkedConvert('timezone', (v) => v as String),
      writable: $checkedConvert('writable', (v) => v as bool),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      canHost: $checkedConvert('can_host', (v) => v as bool),
      children: $checkedConvert(
        'children',
        (v) => (v as List<dynamic>)
            .map((e) => FamilyVisitChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      hosts: $checkedConvert(
        'hosts',
        (v) => (v as List<dynamic>)
            .map((e) => FamilyVisitChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      gateRules: $checkedConvert(
        'gate_rules',
        (v) => FamilyVisitRules.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'canManage': 'can_manage',
    'canHost': 'can_host',
    'gateRules': 'gate_rules',
  },
);

Map<String, dynamic> _$FamilyVisitConfigurationToJson(
  FamilyVisitConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'timezone': instance.timezone,
  'writable': instance.writable,
  'can_manage': instance.canManage,
  'can_host': instance.canHost,
  'children': instance.children.map((e) => e.toJson()).toList(),
  'hosts': instance.hosts.map((e) => e.toJson()).toList(),
  'gate_rules': instance.gateRules.toJson(),
};
