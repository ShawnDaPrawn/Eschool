// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryConfiguration _$GalleryConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GalleryConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'account_id',
        'session_year_id',
        'today',
        'years',
        'children',
        'groups',
        'guardian_required',
        'settings_revision',
        'can_manage',
        'can_write',
      ],
    );
    final val = GalleryConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      accountId: $checkedConvert('account_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      today: $checkedConvert('today', (v) => v as String),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map((e) => GalleryYear.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      children: $checkedConvert(
        'children',
        (v) => (v as List<dynamic>)
            .map((e) => GalleryChild.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map((e) => GalleryGroup.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      guardianRequired: $checkedConvert('guardian_required', (v) => v as bool),
      settingsRevision: $checkedConvert(
        'settings_revision',
        (v) => (v as num).toInt(),
      ),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      canWrite: $checkedConvert('can_write', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'accountId': 'account_id',
    'sessionYearId': 'session_year_id',
    'guardianRequired': 'guardian_required',
    'settingsRevision': 'settings_revision',
    'canManage': 'can_manage',
    'canWrite': 'can_write',
  },
);

Map<String, dynamic> _$GalleryConfigurationToJson(
  GalleryConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'account_id': instance.accountId,
  'session_year_id': instance.sessionYearId,
  'today': instance.today,
  'years': instance.years.map((e) => e.toJson()).toList(),
  'children': instance.children.map((e) => e.toJson()).toList(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'guardian_required': instance.guardianRequired,
  'settings_revision': instance.settingsRevision,
  'can_manage': instance.canManage,
  'can_write': instance.canWrite,
};
