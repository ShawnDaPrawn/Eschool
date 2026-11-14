// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_adjudicators_write_adjudicators_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAdjudicatorsWriteAdjudicatorsInner
_$AcademicAdjudicatorsWriteAdjudicatorsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAdjudicatorsWriteAdjudicatorsInner', json, (
  $checkedConvert,
) {
  final val = AcademicAdjudicatorsWriteAdjudicatorsInner(
    userId: $checkedConvert('user_id', (v) => (v as num?)?.toInt()),
    name: $checkedConvert('name', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'userId': 'user_id'});

Map<String, dynamic> _$AcademicAdjudicatorsWriteAdjudicatorsInnerToJson(
  AcademicAdjudicatorsWriteAdjudicatorsInner instance,
) => <String, dynamic>{'user_id': ?instance.userId, 'name': ?instance.name};
