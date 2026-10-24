// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'curriculum_copy_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CurriculumCopyWrite _$CurriculumCopyWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CurriculumCopyWrite', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['session_year_id']);
      final val = CurriculumCopyWrite(
        sessionYearId: $checkedConvert(
          'session_year_id',
          (v) => (v as num).toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'sessionYearId': 'session_year_id'});

Map<String, dynamic> _$CurriculumCopyWriteToJson(
  CurriculumCopyWrite instance,
) => <String, dynamic>{'session_year_id': instance.sessionYearId};
