// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'withdraw_practice_test_blueprint_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WithdrawPracticeTestBlueprintResponse
_$WithdrawPracticeTestBlueprintResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WithdrawPracticeTestBlueprintResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = WithdrawPracticeTestBlueprintResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) =>
              PracticeTestBlueprintReceipt.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$WithdrawPracticeTestBlueprintResponseToJson(
  WithdrawPracticeTestBlueprintResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
