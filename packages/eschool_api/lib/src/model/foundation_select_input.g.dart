// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_select_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationSelectInput _$FoundationSelectInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationSelectInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'classroom_authorization',
        'locator',
      ],
    );
    final val = FoundationSelectInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      classroomAuthorization: $checkedConvert(
        'classroom_authorization',
        (v) => v as String,
      ),
      locator: $checkedConvert('locator', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'classroomAuthorization': 'classroom_authorization',
  },
);

Map<String, dynamic> _$FoundationSelectInputToJson(
  FoundationSelectInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'classroom_authorization': instance.classroomAuthorization,
  'locator': instance.locator,
};
