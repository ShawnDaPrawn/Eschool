// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationConfiguration _$CommunicationConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'reader_id',
        'timezone',
        'staff_available',
        'can_manage',
        'session_year_id',
        'years',
        'scopes',
        'audiences',
        'rules',
        'channels',
      ],
    );
    final val = CommunicationConfiguration(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
      timezone: $checkedConvert('timezone', (v) => v as String),
      staffAvailable: $checkedConvert('staff_available', (v) => v as bool),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
      years: $checkedConvert(
        'years',
        (v) => (v as List<dynamic>)
            .map(
              (e) => CommunicationConfigurationYearsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      scopes: $checkedConvert(
        'scopes',
        (v) => (v as List<dynamic>)
            .map((e) => CommunicationScope.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      audiences: $checkedConvert(
        'audiences',
        (v) => (v as List<dynamic>)
            .map(
              (e) => CommunicationAudience.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      rules: $checkedConvert(
        'rules',
        (v) => v == null
            ? null
            : CommunicationEffectiveRules.fromJson(v as Map<String, dynamic>),
      ),
      channels: $checkedConvert(
        'channels',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'readerId': 'reader_id',
    'staffAvailable': 'staff_available',
    'canManage': 'can_manage',
    'sessionYearId': 'session_year_id',
  },
);

Map<String, dynamic> _$CommunicationConfigurationToJson(
  CommunicationConfiguration instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'timezone': instance.timezone,
  'staff_available': instance.staffAvailable,
  'can_manage': instance.canManage,
  'session_year_id': instance.sessionYearId,
  'years': instance.years.map((e) => e.toJson()).toList(),
  'scopes': instance.scopes.map((e) => e.toJson()).toList(),
  'audiences': instance.audiences.map((e) => e.toJson()).toList(),
  'rules': instance.rules?.toJson(),
  'channels': instance.channels,
};
