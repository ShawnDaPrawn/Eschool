// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_visit_cancelled.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyVisitCancelled _$FamilyVisitCancelledFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyVisitCancelled', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'status', 'revision']);
  final val = FamilyVisitCancelled(
    id: $checkedConvert('id', (v) => v as String),
    status: $checkedConvert('status', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$FamilyVisitCancelledToJson(
  FamilyVisitCancelled instance,
) => <String, dynamic>{
  'id': instance.id,
  'status': instance.status,
  'revision': instance.revision,
};
