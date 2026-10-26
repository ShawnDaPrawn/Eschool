// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_reference_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayReferenceResult _$PathwayReferenceResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PathwayReferenceResult', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['school_id', 'reader_id', 'reference']);
  final val = PathwayReferenceResult(
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    reference: $checkedConvert(
      'reference',
      (v) => PathwayReference.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$PathwayReferenceResultToJson(
  PathwayReferenceResult instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'reference': instance.reference.toJson(),
};
