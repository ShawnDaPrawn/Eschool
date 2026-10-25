// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_accept_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferAcceptInput _$TransferAcceptInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TransferAcceptInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'fingerprint',
            'session_year_id',
            'class_section_id',
            'category_id',
            'confirmation',
          ],
        );
        final val = TransferAcceptInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          fingerprint: $checkedConvert('fingerprint', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          classSectionId: $checkedConvert(
            'class_section_id',
            (v) => (v as num).toInt(),
          ),
          categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
          confirmation: $checkedConvert('confirmation', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'classSectionId': 'class_section_id',
        'categoryId': 'category_id',
      },
    );

Map<String, dynamic> _$TransferAcceptInputToJson(
  TransferAcceptInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'fingerprint': instance.fingerprint,
  'session_year_id': instance.sessionYearId,
  'class_section_id': instance.classSectionId,
  'category_id': instance.categoryId,
  'confirmation': instance.confirmation,
};
