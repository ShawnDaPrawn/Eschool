// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_term_draft.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTermDraft _$SchoolTermDraftFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SchoolTermDraft',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'name',
            'starts_on',
            'ends_on',
            'grade_from',
            'grade_to',
            'marks_lock_on',
            'reports_on',
          ],
        );
        final val = SchoolTermDraft(
          name: $checkedConvert('name', (v) => v as String),
          startsOn: $checkedConvert(
            'starts_on',
            (v) => DateTime.parse(v as String),
          ),
          endsOn: $checkedConvert(
            'ends_on',
            (v) => DateTime.parse(v as String),
          ),
          gradeFrom: $checkedConvert('grade_from', (v) => (v as num).toInt()),
          gradeTo: $checkedConvert('grade_to', (v) => (v as num).toInt()),
          marksLockOn: $checkedConvert(
            'marks_lock_on',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          reportsOn: $checkedConvert(
            'reports_on',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
        'gradeFrom': 'grade_from',
        'gradeTo': 'grade_to',
        'marksLockOn': 'marks_lock_on',
        'reportsOn': 'reports_on',
      },
    );

Map<String, dynamic> _$SchoolTermDraftToJson(SchoolTermDraft instance) =>
    <String, dynamic>{
      'name': instance.name,
      'starts_on': instance.startsOn.toIso8601String(),
      'ends_on': instance.endsOn.toIso8601String(),
      'grade_from': instance.gradeFrom,
      'grade_to': instance.gradeTo,
      'marks_lock_on': instance.marksLockOn?.toIso8601String(),
      'reports_on': instance.reportsOn?.toIso8601String(),
    };
