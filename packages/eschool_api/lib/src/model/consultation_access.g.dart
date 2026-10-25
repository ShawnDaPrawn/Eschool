// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationAccess _$ConsultationAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ConsultationAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'account_id',
            'session_year_id',
            'today',
            'timezone',
            'writable',
            'can_manage',
          ],
        );
        final val = ConsultationAccess(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          accountId: $checkedConvert('account_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          today: $checkedConvert('today', (v) => v as String),
          timezone: $checkedConvert('timezone', (v) => v as String),
          writable: $checkedConvert('writable', (v) => v as bool),
          canManage: $checkedConvert('can_manage', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'accountId': 'account_id',
        'sessionYearId': 'session_year_id',
        'canManage': 'can_manage',
      },
    );

Map<String, dynamic> _$ConsultationAccessToJson(ConsultationAccess instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'account_id': instance.accountId,
      'session_year_id': instance.sessionYearId,
      'today': instance.today,
      'timezone': instance.timezone,
      'writable': instance.writable,
      'can_manage': instance.canManage,
    };
