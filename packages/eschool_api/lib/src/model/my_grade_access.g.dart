// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeAccess _$MyGradeAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MyGradeAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'session_year_id',
            'today',
            'writable',
            'grades',
          ],
        );
        final val = MyGradeAccess(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          today: $checkedConvert('today', (v) => v as String),
          writable: $checkedConvert('writable', (v) => v as bool),
          grades: $checkedConvert(
            'grades',
            (v) => (v as List<dynamic>)
                .map((e) => MyGradeChoice.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'sessionYearId': 'session_year_id',
      },
    );

Map<String, dynamic> _$MyGradeAccessToJson(MyGradeAccess instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'session_year_id': instance.sessionYearId,
      'today': instance.today,
      'writable': instance.writable,
      'grades': instance.grades.map((e) => e.toJson()).toList(),
    };
