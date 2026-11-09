// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_mark_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeMarkReceipt _$PracticeMarkReceiptFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeMarkReceipt', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'state', 'correct', 'marked_how'],
      );
      final val = PracticeMarkReceipt(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        state: $checkedConvert(
          'state',
          (v) => $enumDecode(
            _$PracticeMarkReceiptStateEnumEnumMap,
            v,
            unknownValue: PracticeMarkReceiptStateEnum.unknownDefaultOpenApi,
          ),
        ),
        correct: $checkedConvert('correct', (v) => v as bool),
        markedHow: $checkedConvert(
          'marked_how',
          (v) => $enumDecode(
            _$PracticeMarkReceiptMarkedHowEnumEnumMap,
            v,
            unknownValue:
                PracticeMarkReceiptMarkedHowEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'markedHow': 'marked_how'});

Map<String, dynamic> _$PracticeMarkReceiptToJson(
  PracticeMarkReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': _$PracticeMarkReceiptStateEnumEnumMap[instance.state]!,
  'correct': instance.correct,
  'marked_how': _$PracticeMarkReceiptMarkedHowEnumEnumMap[instance.markedHow]!,
};

const _$PracticeMarkReceiptStateEnumEnumMap = {
  PracticeMarkReceiptStateEnum.marked: 'marked',
  PracticeMarkReceiptStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$PracticeMarkReceiptMarkedHowEnumEnumMap = {
  PracticeMarkReceiptMarkedHowEnum.teacher: 'teacher',
  PracticeMarkReceiptMarkedHowEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
