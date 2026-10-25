// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_form_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyFormConfiguration _$FamilyFormConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilyFormConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'children',
        'groups',
        'writable',
        'signature_name',
        'timezone',
        'session_year_id',
        'school_id',
        'reader_id',
      ],
    );
    final val = FamilyFormConfiguration(
      children: $checkedConvert(
        'children',
        (v) => (v as List<dynamic>)
            .map((e) => FamilyFormChoice.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => FamilyFormGroupChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      writable: $checkedConvert('writable', (v) => v as bool),
      signatureName: $checkedConvert('signature_name', (v) => v as String),
      timezone: $checkedConvert('timezone', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'signatureName': 'signature_name',
    'sessionYearId': 'session_year_id',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$FamilyFormConfigurationToJson(
  FamilyFormConfiguration instance,
) => <String, dynamic>{
  'children': instance.children.map((e) => e.toJson()).toList(),
  'groups': instance.groups.map((e) => e.toJson()).toList(),
  'writable': instance.writable,
  'signature_name': instance.signatureName,
  'timezone': instance.timezone,
  'session_year_id': instance.sessionYearId,
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};
