// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_mirror.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsMirror _$StrengthsMirrorFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsMirror',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'learner_id',
            'reader_id',
            'first_name',
            'as_of',
            'privacy',
            'sections',
          ],
        );
        final val = StrengthsMirror(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          firstName: $checkedConvert('first_name', (v) => v as String),
          asOf: $checkedConvert('as_of', (v) => DateTime.parse(v as String)),
          privacy: $checkedConvert('privacy', (v) => v as String),
          sections: $checkedConvert(
            'sections',
            (v) => StrengthsSections.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'learnerId': 'learner_id',
        'readerId': 'reader_id',
        'firstName': 'first_name',
        'asOf': 'as_of',
      },
    );

Map<String, dynamic> _$StrengthsMirrorToJson(StrengthsMirror instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'learner_id': instance.learnerId,
      'reader_id': instance.readerId,
      'first_name': instance.firstName,
      'as_of': instance.asOf.toIso8601String(),
      'privacy': instance.privacy,
      'sections': instance.sections.toJson(),
    };
