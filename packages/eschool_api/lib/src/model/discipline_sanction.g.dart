// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_sanction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineSanction _$DisciplineSanctionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('DisciplineSanction', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['code', 'name', 'kind']);
      final val = DisciplineSanction(
        code: $checkedConvert('code', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$DisciplineSanctionKindEnumEnumMap,
            v,
            unknownValue: DisciplineSanctionKindEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    });

Map<String, dynamic> _$DisciplineSanctionToJson(DisciplineSanction instance) =>
    <String, dynamic>{
      'code': instance.code,
      'name': instance.name,
      'kind': _$DisciplineSanctionKindEnumEnumMap[instance.kind]!,
    };

const _$DisciplineSanctionKindEnumEnumMap = {
  DisciplineSanctionKindEnum.notice: 'notice',
  DisciplineSanctionKindEnum.detention: 'detention',
  DisciplineSanctionKindEnum.restorative: 'restorative',
  DisciplineSanctionKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
