// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_family_history_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFamilyHistoryResponse _$DisciplineFamilyHistoryResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineFamilyHistoryResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = DisciplineFamilyHistoryResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => DisciplineFamilyHistory.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$DisciplineFamilyHistoryResponseToJson(
  DisciplineFamilyHistoryResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
