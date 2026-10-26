// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_selection_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwaySelectionResult _$PathwaySelectionResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PathwaySelectionResult',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'valid',
        'selected_subject_ids',
        'missing_compulsory_subject_ids',
        'groups',
      ],
    );
    final val = PathwaySelectionResult(
      valid: $checkedConvert('valid', (v) => v as bool),
      selectedSubjectIds: $checkedConvert(
        'selected_subject_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      missingCompulsorySubjectIds: $checkedConvert(
        'missing_compulsory_subject_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      groups: $checkedConvert(
        'groups',
        (v) => (v as List<dynamic>)
            .map(
              (e) => PathwayElectiveResult.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'selectedSubjectIds': 'selected_subject_ids',
    'missingCompulsorySubjectIds': 'missing_compulsory_subject_ids',
  },
);

Map<String, dynamic> _$PathwaySelectionResultToJson(
  PathwaySelectionResult instance,
) => <String, dynamic>{
  'valid': instance.valid,
  'selected_subject_ids': instance.selectedSubjectIds,
  'missing_compulsory_subject_ids': instance.missingCompulsorySubjectIds,
  'groups': instance.groups.map((e) => e.toJson()).toList(),
};
