// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAccess _$RecognitionAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RecognitionAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'module',
            'school_id',
            'account_id',
            'session_year_id',
            'today',
            'family',
            'can_write',
            'manager',
            'grade_ids',
          ],
        );
        final val = RecognitionAccess(
          module: $checkedConvert('module', (v) => v as String),
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          accountId: $checkedConvert('account_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          today: $checkedConvert('today', (v) => v as String),
          family: $checkedConvert('family', (v) => v as bool),
          canWrite: $checkedConvert('can_write', (v) => v as bool),
          manager: $checkedConvert('manager', (v) => v as bool),
          gradeIds: $checkedConvert(
            'grade_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'accountId': 'account_id',
        'sessionYearId': 'session_year_id',
        'canWrite': 'can_write',
        'gradeIds': 'grade_ids',
      },
    );

Map<String, dynamic> _$RecognitionAccessToJson(RecognitionAccess instance) =>
    <String, dynamic>{
      'module': instance.module,
      'school_id': instance.schoolId,
      'account_id': instance.accountId,
      'session_year_id': instance.sessionYearId,
      'today': instance.today,
      'family': instance.family,
      'can_write': instance.canWrite,
      'manager': instance.manager,
      'grade_ids': instance.gradeIds,
    };
