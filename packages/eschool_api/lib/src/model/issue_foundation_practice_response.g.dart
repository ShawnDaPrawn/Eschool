// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'issue_foundation_practice_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

IssueFoundationPracticeResponse _$IssueFoundationPracticeResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('IssueFoundationPracticeResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = IssueFoundationPracticeResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => PracticeDailySet.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$IssueFoundationPracticeResponseToJson(
  IssueFoundationPracticeResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
