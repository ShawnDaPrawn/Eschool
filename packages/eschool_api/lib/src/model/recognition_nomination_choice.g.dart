// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominationChoice _$RecognitionNominationChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionNominationChoice',
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
    final val = RecognitionNominationChoice(
      categoryId: $checkedConvert('category_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      opensOn: $checkedConvert('opens_on', (v) => v as String),
      closesOn: $checkedConvert('closes_on', (v) => v as String),
      status: $checkedConvert('status', (v) => v as String),
      definitions: $checkedConvert(
        'definitions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  RecognitionNamedReference.fromJson(e as Map<String, dynamic>),
            )
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

Map<String, dynamic> _$RecognitionNominationChoiceToJson(
  RecognitionNominationChoice instance,
) => <String, dynamic>{
  'category_id': instance.categoryId,
  'name': instance.name,
  'opens_on': instance.opensOn,
  'closes_on': instance.closesOn,
  'status': instance.status,
  'definitions': instance.definitions.map((e) => e.toJson()).toList(),
};
