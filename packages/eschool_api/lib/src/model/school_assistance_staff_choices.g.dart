// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_staff_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceStaffChoices _$SchoolAssistanceStaffChoicesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceStaffChoices', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['staff', 'school_id', 'reader_id']);
  final val = SchoolAssistanceStaffChoices(
    staff: $checkedConvert(
      'staff',
      (v) => (v as List<dynamic>)
          .map(
            (e) =>
                SchoolAssistanceStaffChoice.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$SchoolAssistanceStaffChoicesToJson(
  SchoolAssistanceStaffChoices instance,
) => <String, dynamic>{
  'staff': instance.staff.map((e) => e.toJson()).toList(),
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};
