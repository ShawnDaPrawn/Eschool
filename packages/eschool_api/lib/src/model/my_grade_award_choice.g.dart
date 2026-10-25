// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_award_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeAwardChoice _$MyGradeAwardChoiceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MyGradeAwardChoice',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'category_id',
            'name',
            'opens_on',
            'closes_on',
            'status',
            'definitions',
          ],
        );
        final val = MyGradeAwardChoice(
          categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
          name: $checkedConvert('name', (v) => v as String),
          opensOn: $checkedConvert('opens_on', (v) => v as String),
          closesOn: $checkedConvert('closes_on', (v) => v as String),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$MyGradeAwardChoiceStatusEnumEnumMap,
              v,
              unknownValue: MyGradeAwardChoiceStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          definitions: $checkedConvert(
            'definitions',
            (v) => (v as List<dynamic>)
                .map((e) => MyGradeChoice.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'categoryId': 'category_id',
        'opensOn': 'opens_on',
        'closesOn': 'closes_on',
      },
    );

Map<String, dynamic> _$MyGradeAwardChoiceToJson(MyGradeAwardChoice instance) =>
    <String, dynamic>{
      'category_id': instance.categoryId,
      'name': instance.name,
      'opens_on': instance.opensOn,
      'closes_on': instance.closesOn,
      'status': _$MyGradeAwardChoiceStatusEnumEnumMap[instance.status]!,
      'definitions': instance.definitions.map((e) => e.toJson()).toList(),
    };

const _$MyGradeAwardChoiceStatusEnumEnumMap = {
  MyGradeAwardChoiceStatusEnum.open: 'open',
  MyGradeAwardChoiceStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
