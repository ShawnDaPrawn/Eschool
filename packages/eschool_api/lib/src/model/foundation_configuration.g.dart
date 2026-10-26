// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationConfiguration _$FoundationConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'session_year_id',
        'can_manage',
        'can_grant',
        'writable',
        'page',
        'next_page',
        'groups',
        'devices',
        'leases',
      ],
    );
    final val = FoundationConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      canGrant: $checkedConvert('can_grant', (v) => v as bool),
      writable: $checkedConvert('writable', (v) => v as bool),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => FoundationGroupChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      devices: $checkedConvert(
        'devices',
        (v) => (v as List<dynamic>)
            .map((e) => FoundationTablet.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      leases: $checkedConvert(
        'leases',
        (v) => (v as List<dynamic>)
            .map(
              (e) => FoundationLeaseSummary.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'sessionYearId': 'session_year_id',
    'canManage': 'can_manage',
    'canGrant': 'can_grant',
    'nextPage': 'next_page',
  },
);

Map<String, dynamic> _$FoundationConfigurationToJson(
  FoundationConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'session_year_id': instance.sessionYearId,
  'can_manage': instance.canManage,
  'can_grant': instance.canGrant,
  'writable': instance.writable,
  'page': instance.page,
  'next_page': instance.nextPage,
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'devices': instance.devices.map((e) => e.toJson()).toList(),
  'leases': instance.leases.map((e) => e.toJson()).toList(),
};
